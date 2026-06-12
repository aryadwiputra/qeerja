<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskBulkOperationRequest;
use App\Models\Project;
use App\Models\Workspace;
use App\Services\TaskBulkOperationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class TaskBulkOperationController extends Controller
{
    public function store(StoreTaskBulkOperationRequest $request, Workspace $workspace, Project $project, TaskBulkOperationService $bulkOperations): RedirectResponse
    {
        $count = $bulkOperations->apply($project, $request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => trans_choice(':count task updated.|:count tasks updated.', $count, ['count' => $count])]);

        return back();
    }
}
