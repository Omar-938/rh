<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifie l'employé que sa demande de congé a été approuvée.
 */
class LeaveRequestApproved extends Notification implements ShouldQueue
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
            'title'      => 'Congé approuvé ✅',
            'body'       => sprintf(
                'Votre congé %s (%s) a été approuvé.',
                $this->leaveRequest->leaveType->name,
                $this->leaveRequest->period_label,
            ),
            'icon'       => '✅',
            'type'       => 'leave_approved',
            'action_url' => route('leaves.show', $this->leaveRequest->id),
            'leave_id'   => $this->leaveRequest->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $leaveType = $this->leaveRequest->leaveType;
        $period    = $this->leaveRequest->period_label;
        $days      = $this->leaveRequest->days_count;
        $reviewer  = $this->leaveRequest->reviewer;

        return (new MailMessage)
            ->subject("Votre demande de congé a été approuvée")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line('Bonne nouvelle ! Votre demande de congé a été approuvée.')
            ->line("**Type :** {$leaveType->name}")
            ->line("**Période :** {$period}")
            ->line("**Durée :** {$days} jour(s) ouvré(s)")
            ->when($reviewer, fn ($mail) =>
                $mail->line("**Approuvé par :** {$reviewer->full_name}")
            )
            ->when($this->leaveRequest->reviewer_comment, fn ($mail) =>
                $mail->line("**Commentaire :** {$this->leaveRequest->reviewer_comment}")
            )
            ->action('Voir ma demande', route('leaves.show', $this->leaveRequest->id))
            ->salutation('L\'équipe SimpliRH');
    }
}
