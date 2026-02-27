<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifie les admins / managers qu'une nouvelle demande de congé a été soumise.
 */
class LeaveRequestSubmitted extends Notification implements ShouldQueue
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
            'title'      => 'Nouvelle demande de congé',
            'body'       => sprintf(
                '%s demande %s j de %s (%s).',
                $this->leaveRequest->user->full_name,
                $this->leaveRequest->days_count,
                $this->leaveRequest->leaveType->name,
                $this->leaveRequest->period_label,
            ),
            'icon'       => '📋',
            'type'       => 'leave_submitted',
            'action_url' => route('leaves.show', $this->leaveRequest->id),
            'leave_id'   => $this->leaveRequest->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $employee   = $this->leaveRequest->user;
        $leaveType  = $this->leaveRequest->leaveType;
        $days       = $this->leaveRequest->days_count;
        $period     = $this->leaveRequest->period_label;

        return (new MailMessage)
            ->subject("Nouvelle demande de congé — {$employee->full_name}")
            ->greeting('Bonjour,')
            ->line(
                "{$employee->full_name} a soumis une demande de congé nécessitant votre validation."
            )
            ->line("**Type :** {$leaveType->name}")
            ->line("**Période :** {$period}")
            ->line("**Durée :** {$days} jour(s) ouvré(s)")
            ->when($this->leaveRequest->employee_comment, fn ($mail) =>
                $mail->line("**Commentaire :** {$this->leaveRequest->employee_comment}")
            )
            ->action('Voir la demande', route('leaves.show', $this->leaveRequest->id))
            ->line('Vous pouvez approuver ou refuser cette demande depuis SimpliRH.')
            ->salutation('L\'équipe SimpliRH');
    }
}
