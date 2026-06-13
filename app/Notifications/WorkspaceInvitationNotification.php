<?php

namespace App\Notifications;

use App\Models\WorkspaceInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public WorkspaceInvitation $invitation) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Invitation to join {$this->invitation->workspace->name}")
            ->greeting('Hello,')
            ->line("{$this->invitation->invitedBy->name} invited you to join {$this->invitation->workspace->name} as {$this->invitation->role}.")
            ->action('Accept Invitation', route('workspace-invitations.accept', ['invitation' => $this->invitation->token]))
            ->line('This invitation expires in 7 days.');
    }
}
