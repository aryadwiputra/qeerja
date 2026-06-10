<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateWorkspaceSettingsRequest;
use App\Models\Workspace;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class WorkspaceSettingController extends Controller
{
    public function update(UpdateWorkspaceSettingsRequest $request, Workspace $workspace, SettingService $settings): RedirectResponse
    {
        $data = $request->validatedWithKeys();

        if ($data !== []) {
            $settings->bulk($workspace, $data);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Workspace settings saved.']);

        return back();
    }
}
