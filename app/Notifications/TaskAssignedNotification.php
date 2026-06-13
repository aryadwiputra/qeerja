<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Task $task,
        public User $assignedBy,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('projects.show', [
            'workspace' => $this->task->project->workspace->slug,
            'project' => $this->task->project->slug,
        ]);

        $mail = (new MailMessage)
            ->subject("Assigned: {$this->task->code} - {$this->task->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->assignedBy->name} assigned you to {$this->task->code}: {$this->task->title}.")
            ->action('Open Project', $url)
            ->line("Project: {$this->task->project->name}");

        if ($this->task->due_date) {
            $mail->line("Due: {$this->task->due_date->format('M j, Y')}");
        }

        return $mail;
    }
}
