<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;

class GitHubAuthController extends Controller
{
    public function redirect(Workspace $workspace, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        session()->put('gh_workspace_id', $workspace->id);
        session()->put('gh_project_id', $project->id);

        return Socialite::driver('github')
            ->scopes(['repo', 'admin:repo_hook'])
            ->with(['state' => encrypt(json_encode([
                'workspace_id' => $workspace->id,
                'project_id' => $project->id,
            ]))])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $workspaceId = $request->session()->pull('gh_workspace_id');
        $projectId = $request->session()->pull('gh_project_id');

        if (! $workspaceId || ! $projectId) {
            return redirect()->route('dashboard')->withErrors('GitHub connection failed: session expired.');
        }

        $user = $request->user();
        $project = Project::find($projectId);

        if (! $user || ! $project || ! $user->can('update', $project)) {
            return redirect()->route('dashboard')->withErrors('GitHub connection failed: unauthorized.');
        }

        try {
            $githubUser = Socialite::driver('github')->user();
        } catch (\Exception $e) {
            return redirect()->route('projects.settings', [$workspaceId, $projectId])
                ->withErrors('GitHub authorization failed: '.$e->getMessage());
        }

        $workspace = Workspace::find($workspaceId);

        if (! $workspace) {
            return redirect()->route('dashboard')->withErrors('GitHub connection failed: workspace not found.');
        }

        Integration::updateOrCreate(
            ['project_id' => $project->id, 'provider' => 'github'],
            [
                'workspace_id' => $workspace->id,
                'provider_user_id' => (string) $githubUser->getId(),
                'access_token' => $githubUser->token,
                'refresh_token' => $githubUser->refreshToken,
                'expires_at' => $githubUser->expiresIn ? now()->addSeconds($githubUser->expiresIn) : null,
                'metadata' => [
                    'nickname' => $githubUser->getNickname(),
                    'name' => $githubUser->getName(),
                    'avatar' => $githubUser->getAvatar(),
                ],
            ],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'GitHub connected successfully.']);

        return redirect()->route('projects.settings', [$workspace, $project]);
    }

    public function destroy(Workspace $workspace, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $integration = Integration::where('project_id', $project->id)
            ->where('provider', 'github')
            ->firstOrFail();

        $integration->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'GitHub disconnected.']);

        return redirect()->route('projects.settings', [$workspace, $project]);
    }
}
