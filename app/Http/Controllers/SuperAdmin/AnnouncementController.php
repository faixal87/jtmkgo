<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Jobs\SendAnnouncementEmail;
use App\Models\AnnouncementTemplate;
use App\Models\EmailLog;
use App\Models\User;
use App\Support\AnnouncementTemplates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    /**
     * Maximum announcement emails dispatched per minute during a blast.
     */
    private const EMAILS_PER_MINUTE = 30;

    public function create(Request $request): View
    {
        $customTemplates = Schema::hasTable('announcement_templates')
            ? AnnouncementTemplate::query()->orderBy('label')->get()
            : collect();

        $templates = collect(AnnouncementTemplates::all())
            ->map(fn (array $template): array => [
                'uid' => 'builtin:'.$template['key'],
                'label' => $template['label'].' (Built-in)',
                'subject' => $template['subject'],
                'html' => $template['html'],
            ])
            ->concat($customTemplates->map(fn (AnnouncementTemplate $template): array => [
                'uid' => 'custom:'.$template->id,
                'label' => $template->label,
                'subject' => $template->subject,
                'html' => $template->html,
            ]))
            ->values();

        return view('super-admin.announcements.create', [
            'templates' => $templates,
            'customTemplates' => $customTemplates,
            'users' => User::query()
                ->select(['id', 'name', 'email'])
                ->where('account_status', 'approved')
                ->whereNotNull('email')
                ->orderBy('name')
                ->get(),
            'activeTab' => $request->query('tab') === 'templates' ? 'templates' : 'send',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recipients' => ['required', Rule::in(['all', 'selected'])],
            'user_ids' => ['required_if:recipients,selected', 'nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string', 'max:200000'],
        ]);

        $recipients = User::query()
            ->select(['id', 'name', 'email'])
            ->where('account_status', 'approved')
            ->whereNotNull('email')
            ->when(
                $validated['recipients'] === 'selected',
                fn ($query) => $query->whereIn('id', $validated['user_ids'] ?? [])
            )
            ->orderBy('name')
            ->get();

        if ($recipients->isEmpty()) {
            return back()->withInput()->with('error', 'No eligible recipients were found.');
        }

        foreach ($recipients->values() as $index => $recipient) {
            $log = EmailLog::query()->create([
                'user_id' => $recipient->id,
                'recipient_email' => $recipient->email,
                'subject' => AnnouncementTemplates::personalize($validated['subject'], $recipient->name),
                'type' => 'announcement',
                'status' => EmailLog::STATUS_QUEUED,
            ]);

            $delaySeconds = intdiv($index, self::EMAILS_PER_MINUTE) * 60;

            SendAnnouncementEmail::dispatch(
                $log->id,
                $recipient->id,
                $validated['subject'],
                $validated['body_html'],
            )->delay($delaySeconds > 0 ? now()->addSeconds($delaySeconds) : null);
        }

        $count = $recipients->count();
        $batches = (int) ceil($count / self::EMAILS_PER_MINUTE);

        return back()->with('status', "Announcement queued for {$count} recipient(s) in {$batches} batch(es) — 30 emails per minute.");
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:150'],
            'subject' => ['required', 'string', 'max:255'],
            'html' => ['required', 'string', 'max:200000'],
        ]);

        $template = AnnouncementTemplate::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('super-admin.announcements.create', ['tab' => 'templates'])
            ->with('status', "Template \"{$template->label}\" has been added.");
    }

    public function destroyTemplate(AnnouncementTemplate $template): RedirectResponse
    {
        $label = $template->label;
        $template->delete();

        return redirect()
            ->route('super-admin.announcements.create', ['tab' => 'templates'])
            ->with('status', "Template \"{$label}\" has been deleted.");
    }
}
