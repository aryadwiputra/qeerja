<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(30)
            ->through(fn ($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'body' => $n->body,
                'data' => $n->data,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at,
            ]);

        return Inertia::render('notifications/index', [
            'notifications' => $notifications,
            'unreadCount' => $request->user()->notifications()->unread()->count(),
        ]);
    }

    public function markAsRead(Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === request()->user()->id, 403);

        $notification->markAsRead();

        return back();
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->notifications()->unread()->update(['read_at' => now()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'All notifications marked as read.']);

        return back();
    }

    public function destroy(Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === request()->user()->id, 403);

        $notification->delete();

        return back();
    }
}
