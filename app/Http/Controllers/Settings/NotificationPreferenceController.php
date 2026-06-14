<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationPreferenceController extends Controller
{
    public function edit(Request $request): Response
    {
        $preferences = NotificationPreference::where('user_id', $request->user()->id)
            ->get()
            ->keyBy('type');

        $notificationTypes = [
            'task.assigned' => 'Task assigned',
            'task.commented' => 'Task commented',
            'task.mentioned' => 'Task mentioned',
            'task.updated' => 'Task updated',
            'workspace.invitation' => 'Workspace invitation',
        ];

        $result = [];
        foreach ($notificationTypes as $type => $label) {
            $preference = $preferences->get($type);
            $result[$type] = [
                'label' => $label,
                'in_app_enabled' => $preference?->in_app_enabled ?? true,
                'email_enabled' => $preference?->email_enabled ?? true,
            ];
        }

        return Inertia::render('settings/notifications', [
            'preferences' => $result,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.*.in_app_enabled' => 'required|boolean',
            'preferences.*.email_enabled' => 'required|boolean',
        ]);

        $user = $request->user();

        foreach ($validated['preferences'] as $type => $settings) {
            NotificationPreference::updateOrCreate(
                ['user_id' => $user->id, 'type' => $type],
                [
                    'in_app_enabled' => $settings['in_app_enabled'],
                    'email_enabled' => $settings['email_enabled'],
                ]
            );
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Notification preferences updated.')]);

        return to_route('notifications.edit');
    }
}
