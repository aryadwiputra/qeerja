<?php

namespace App\Listeners;

use App\Events\TaskCreated;
use App\Models\Task;
use App\Services\AutomationEngine;

class DispatchTaskCreatedAutomation
{
    public function __construct(
        protected AutomationEngine $engine,
    ) {}

    public function handle(TaskCreated $event): void
    {
        $task = Task::with('project', 'labels', 'assignees', 'epics')->find($event->taskId);

        if (! $task) {
            return;
        }

        $this->engine->handleTaskEvent($task, 'task.created');
    }
}
