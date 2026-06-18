<?php

namespace App\Listeners;

use App\Events\TaskFieldUpdated;
use App\Models\Task;
use App\Services\AutomationEngine;

class DispatchAutomationEvents
{
    public function __construct(
        protected AutomationEngine $engine,
    ) {}

    public function handle(TaskFieldUpdated $event): void
    {
        $task = Task::with('project', 'labels', 'assignees', 'epics')->find($event->taskId);

        if (! $task) {
            return;
        }

        $triggerMap = [
            'board_column_id' => 'task.status_changed',
            'priority_id' => 'task.priority_changed',
        ];

        foreach ($event->changes as $field => $newValue) {
            $trigger = $triggerMap[$field] ?? null;

            if ($trigger) {
                $this->engine->handleTaskEvent($task, $trigger, [
                    'field' => $field,
                    'value' => $newValue,
                ]);
            }
        }
    }
}
