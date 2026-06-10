<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class NotificationCenterController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => $request->user()
                ->notifications()
                ->latest()
                ->paginate(15),
        ]);
    }

    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();
        $select = ['id', 'user_id', 'title', 'message', 'read_at', 'created_at'];

        if ($this->hasActionColumns()) {
            $select[] = 'action_url';
            $select[] = 'action_label';
        }

        return response()->json([
            'unread_count' => $user->notifications()->whereNull('read_at')->count(),
            'notifications' => $user->notifications()
                ->select($select)
                ->latest()
                ->take(8)
                ->get()
                ->map(fn (Notification $notification) => [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'created_at' => $notification->created_at?->diffForHumans(),
                    'is_read' => $notification->read_at !== null,
                    'action_url' => $notification->action_url,
                    'action_label' => $notification->action_label ?: 'Open',
                ]),
        ]);
    }

    public function markRead(Request $request, Notification $notification): JsonResponse|RedirectResponse
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 403);

        $notification->markAsRead();
        Cache::forget("notifications.unread-count.{$request->user()->id}");

        if ($request->expectsJson()) {
            return response()->json(['status' => 'read']);
        }

        return back()->with('status', 'Notification marked as read.');
    }

    public function markUnread(Request $request, Notification $notification): JsonResponse|RedirectResponse
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 403);

        $notification->forceFill(['read_at' => null])->save();
        Cache::forget("notifications.unread-count.{$request->user()->id}");

        if ($request->expectsJson()) {
            return response()->json(['status' => 'unread']);
        }

        return back()->with('status', 'Notification marked as unread.');
    }

    public function markAllRead(Request $request): JsonResponse|RedirectResponse
    {
        $request->user()
            ->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);
        Cache::forget("notifications.unread-count.{$request->user()->id}");

        if ($request->expectsJson()) {
            return response()->json(['status' => 'read']);
        }

        return back()->with('status', 'All notifications marked as read.');
    }

    private function hasActionColumns(): bool
    {
        static $hasColumns = null;

        return $hasColumns ??= Schema::hasTable('notifications')
            && Schema::hasColumn('notifications', 'action_url')
            && Schema::hasColumn('notifications', 'action_label');
    }
}
