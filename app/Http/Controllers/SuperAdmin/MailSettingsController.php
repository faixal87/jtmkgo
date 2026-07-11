<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Mail\MailSettingsTestMail;
use App\Models\EmailLog;
use App\Support\MailSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class MailSettingsController extends Controller
{
    public function edit(MailSettings $mailSettings): View
    {
        $settings = $mailSettings->all();
        $settings['smtp_password'] = '';
        $settings['ses_smtp_password'] = '';

        return view('super-admin.settings.mail', [
            'settings' => $settings,
            'hasSmtpPassword' => $mailSettings->hasSecret('smtp_password'),
            'hasSesPassword' => $mailSettings->hasSecret('ses_smtp_password'),
        ]);
    }

    public function update(Request $request, MailSettings $mailSettings): RedirectResponse
    {
        $validated = $request->validate([
            'mail_provider' => ['required', Rule::in(['smtp', 'ses'])],
            'mail_notifications_enabled' => ['nullable', 'boolean'],

            'smtp_mailer' => ['required_if:mail_provider,smtp', 'nullable', Rule::in(['smtp', 'log', 'array'])],
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_encryption' => ['nullable', Rule::in(['tls', 'ssl', ''])],

            'ses_region' => ['required_if:mail_provider,ses', 'nullable', 'string', 'max:64'],
            'ses_smtp_username' => ['required_if:mail_provider,ses', 'nullable', 'string', 'max:255'],
            'ses_smtp_password' => ['nullable', 'string', 'max:255'],

            'mail_from_address' => ['required', 'email', 'max:255'],
            'mail_from_name' => ['required', 'string', 'max:255'],
            'mail_ehlo_domain' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validated['mail_provider'] === 'smtp' && ($validated['smtp_mailer'] ?? 'smtp') === 'smtp') {
            $request->validate([
                'smtp_host' => ['required', 'string', 'max:255'],
                'smtp_port' => ['required', 'integer', 'min:1', 'max:65535'],
            ]);
        }

        $validated['mail_notifications_enabled'] = $request->boolean('mail_notifications_enabled') ? '1' : '0';

        $mailSettings->update($validated);

        return back()->with('status', 'Mail settings have been saved.');
    }

    public function sendTest(Request $request, MailSettings $mailSettings): RedirectResponse
    {
        $validated = $request->validate([
            'test_email' => ['required', 'email', 'max:255'],
        ]);

        $label = $mailSettings->get('mail_provider') === 'ses'
            ? 'Amazon SES'
            : strtoupper($mailSettings->get('smtp_mailer') ?? 'smtp');

        $log = EmailLog::query()->create([
            'user_id' => $request->user()->id,
            'recipient_email' => $validated['test_email'],
            'subject' => 'Test Email from JTMK Go!',
            'type' => 'test',
            'status' => EmailLog::STATUS_QUEUED,
        ]);

        try {
            $mailSettings->applyToRuntimeConfig();

            Mail::to($validated['test_email'])->send(new MailSettingsTestMail($label));
        } catch (Throwable $e) {
            $log->markFailed($e->getMessage());

            return back()->with('error', 'Test email failed: '.$e->getMessage());
        }

        $log->markSent();

        return back()->with('status', "Test email sent to {$validated['test_email']}.");
    }
}
