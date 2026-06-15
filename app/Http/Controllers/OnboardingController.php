<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $onboardingWorkspaceId = session('onboarding_workspace_id');

        if ($user->workspaces()->count() >= 1 && ! $onboardingWorkspaceId) {
            return to_route('dashboard');
        }

        $workspace = $onboardingWorkspaceId
            ? $user->workspaces()->find($onboardingWorkspaceId)
            : $user->workspaces()->first();

        if ($onboardingWorkspaceId) {
            session()->forget('onboarding_workspace_id');
        }

        return Inertia::render('onboarding/index', [
            'hasWorkspace' => $workspace !== null,
            'hasProject' => $workspace?->projects()->count() >= 1,
            'currentWorkspace' => $workspace ? [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
            ] : null,
        ]);
    }
}
