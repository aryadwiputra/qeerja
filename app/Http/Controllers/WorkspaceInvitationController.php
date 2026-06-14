<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkspaceInvitationRequest;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Notifications\WorkspaceInvitationNotification;
use App\Services\WorkspaceRoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Inertia;

class WorkspaceInvitationController extends Controller
{
    public function store(StoreWorkspaceInvitationRequest $request, Workspace $workspace): RedirectResponse
    {
        $validated = $request->validated();
        $email = Str::lower($validated['email']);
        $existingUser = User::where('email', $email)->first();

        if ($existingUser && $workspace->members()->where('user_id', $existingUser->id)->exists()) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => 'User is already a workspace member.']);

            return back();
        }

        $invitation = WorkspaceInvitation::query()->updateOrCreate([
            'workspace_id' => $workspace->id,
            'email' => $email,
            'accepted_at' => null,
        ], [
            'role' => $validated['role'],
            'token' => Str::random(64),
            'invited_by' => $request->user()->id,
            'expired_at' => now()->addDays(7),
        ]);

        if ($existingUser && NotificationPreference::isEmailEnabled($existingUser, 'workspace.invitation')) {
            Notification::route('mail', $email)
                ->notify(new WorkspaceInvitationNotification($invitation->load(['workspace', 'invitedBy'])));
        } elseif (! $existingUser) {
            Notification::route('mail', $email)
                ->notify(new WorkspaceInvitationNotification($invitation->load(['workspace', 'invitedBy'])));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Invitation sent.']);

        return back();
    }

    public function destroy(Workspace $workspace, WorkspaceInvitation $invitation): RedirectResponse
    {
        Gate::authorize('manageMembers', $workspace);
        abort_unless((int) $invitation->workspace_id === (int) $workspace->id, 404);

        if ($invitation->accepted_at !== null) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => 'Accepted invitations cannot be cancelled.']);

            return back();
        }

        $invitation->delete();

        Inertia::flash('toast', ['type' => 'info', 'message' => 'Invitation cancelled.']);

        return back();
    }

    public function accept(Request $request, WorkspaceInvitation $invitation, WorkspaceRoleService $roleService): RedirectResponse
    {
        if (! $invitation->isPending()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'This invitation is no longer valid.']);

            return to_route('dashboard');
        }

        if (! hash_equals(Str::lower($request->user()->email), Str::lower($invitation->email))) {
            abort(403, 'This invitation belongs to another email address.');
        }

        $workspace = $invitation->workspace;
        $existingMember = $workspace->members()->where('user_id', $request->user()->id)->first();

        if (! $existingMember) {
            $workspace->members()->create([
                'user_id' => $request->user()->id,
                'role' => $invitation->role,
                'joined_at' => now(),
                'invited_by' => $invitation->invited_by,
                'status' => 'active',
            ]);

            $roleService->syncRole($request->user(), $workspace, $invitation->role);
        }

        $invitation->update(['accepted_at' => now()]);
        session()->put('current_workspace_id', $workspace->id);
        setPermissionsTeamId($workspace->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => "You joined {$workspace->name}."]);

        return to_route('workspaces.settings', $workspace);
    }
}
