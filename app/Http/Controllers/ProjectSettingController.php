<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProjectSettingsRequest;
use App\Models\Project;
use App\Models\Workspace;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ProjectSettingController extends Controller
{
    public function update(UpdateProjectSettingsRequest $request, Workspace $workspace, Project $project, SettingService $settings): RedirectResponse
    {
        $data = $request->validatedWithKeys();

        if ($data !== []) {
            $settings->bulk($project, $data);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Project settings saved.']);

        return back();
    }
}
