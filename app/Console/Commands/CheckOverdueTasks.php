<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Services\AutomationEngine;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('automation:check-due-dates')]
#[Description('Check for overdue tasks and trigger automation rules')]
class CheckOverdueTasks extends Command
{
    public function handle(AutomationEngine $engine): int
    {
        $overdueTasks = Task::whereNull('completed_at')
            ->whereNull('archived_at')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->get();

        $this->info("Found {$overdueTasks->count()} overdue tasks.");

        foreach ($overdueTasks as $task) {
            $engine->handleTaskEvent($task, 'task.due_date_passed');
            $this->line("  - Processed: {$task->code} (due: {$task->due_date->format('Y-m-d')})");
        }

        return self::SUCCESS;
    }
}
