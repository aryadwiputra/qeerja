<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskMentionedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Task $task,
        public TaskComment $comment,
        public User $commenter,
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

        return (new MailMessage)
            ->subject("Mentioned in {$this->task->code}: {$this->task->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->commenter->name} mentioned you in {$this->task->code}.")
            ->line(str($this->comment->body)->limit(180)->toString())
            ->action('Open Project', $url)
            ->line("Project: {$this->task->project->name}");
    }
}
