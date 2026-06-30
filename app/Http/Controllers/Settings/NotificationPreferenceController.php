<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\NotificationChannel;
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
            ->groupBy('type');

        $notificationTypes = [
            'task.assigned' => 'Task assigned',
            'task.commented' => 'Task commented',
            'task.mentioned' => 'Task mentioned',
            'task.updated' => 'Task updated',
            'workspace.invitation' => 'Workspace invitation',
        ];

        $userWorkspaceIds = $request->user()->workspaces()->pluck('workspaces.id');

        $externalChannels = NotificationChannel::enabled()
            ->whereIn('workspace_id', $userWorkspaceIds)
            ->get()
            ->map(fn ($c) => [
                'key' => $c->driver,
                'name' => $c->name,
                'label' => ucfirst($c->driver),
            ]);

        $builtInChannels = [
            ['key' => 'in_app', 'name' => 'In-App', 'label' => 'In-App'],
            ['key' => 'email', 'name' => 'Email', 'label' => 'Email'],
            ['key' => 'whatsapp', 'name' => 'WhatsApp', 'label' => 'WhatsApp'],
        ];

        $allChannels = collect([...$builtInChannels, ...$externalChannels])->unique('key')->values();

        $result = [];
        foreach ($notificationTypes as $type => $label) {
            $row = ['label' => $label, 'channels' => []];

            foreach ($allChannels as $channel) {
                $pref = $preferences->get($type)?->firstWhere('channel', $channel['key']);
                $defaults = ['in_app' => true, 'email' => true, 'whatsapp' => false];
                $row['channels'][$channel['key']] = $pref?->enabled ?? ($defaults[$channel['key']] ?? true);
            }

            $result[$type] = $row;
        }

        return Inertia::render('settings/notifications', [
            'preferences' => $result,
            'channels' => $allChannels,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.*.channels' => 'required|array',
            'preferences.*.channels.*' => 'boolean',
        ]);

        $user = $request->user();

        foreach ($validated['preferences'] as $type => $settings) {
            foreach ($settings['channels'] as $channel => $enabled) {
                NotificationPreference::updateOrCreate(
                    ['user_id' => $user->id, 'type' => $type, 'channel' => $channel],
                    ['enabled' => $enabled],
                );
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Notification preferences updated.')]);

        return to_route('notifications.edit');
    }
}
