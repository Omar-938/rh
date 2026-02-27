<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifie l'employé que sa demande de congé a été refusée.
 */
class LeaveRequestRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly LeaveRequest $leaveRequest) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'      => 'Congé refusé',
            'body'       => sprintf(
                'Votre demande de %s (%s) a été refusée.%s',
                $this->leaveRequest->leaveType->name,
                $this->leaveRequest->period_label,
                $this->leaveRequest->reviewer_comment
                    ? ' Motif : ' . $this->leaveRequest->reviewer_comment
                    : '',
            ),
            'icon'       => '❌',
            'type'       => 'leave_rejected',
            'action_url' => route('leaves.show', $this->leaveRequest->id),
            'leave_id'   => $this->leaveRequest->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $leaveType = $this->leaveRequest->leaveType;
        $period    = $this->leaveRequest->period_label;
        $reviewer  = $this->leaveRequest->reviewer;

        return (new MailMessage)
            ->subject("Votre demande de congé a été refusée")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line('Votre demande de congé n\'a pas pu être approuvée.')
            ->line("**Type :** {$leaveType->name}")
            ->line("**Période :** {$period}")
            ->when($reviewer, fn ($mail) =>
                $mail->line("**Refusé par :** {$reviewer->full_name}")
            )
            ->when($this->leaveRequest->reviewer_comment, fn ($mail) =>
                $mail->line("**Motif :** {$this->leaveRequest->reviewer_comment}")
            )
            ->line('Si vous souhaitez en discuter, rapprochez-vous de votre responsable.')
            ->action('Voir ma demande', route('leaves.show', $this->leaveRequest->id))
            ->salutation('L\'équipe SimpliRH');
    }
}
