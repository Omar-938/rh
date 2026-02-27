<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifie les admins/managers qu'une demande a été annulée par l'employé,
 * ou notifie l'employé que son congé approuvé a été annulé par un admin.
 */
class LeaveRequestCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param bool $byEmployee  true = l'employé a annulé, false = l'admin a annulé
     */
    public function __construct(
        public readonly LeaveRequest $leaveRequest,
        public readonly bool $byEmployee = true,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        if ($this->byEmployee) {
            return [
                'title'      => 'Demande annulée',
                'body'       => sprintf(
                    '%s a annulé sa demande de %s (%s).',
                    $this->leaveRequest->user->full_name,
                    $this->leaveRequest->leaveType->name,
                    $this->leaveRequest->period_label,
                ),
                'icon'       => '🚫',
                'type'       => 'leave_cancelled',
                'action_url' => route('leaves.index'),
                'leave_id'   => $this->leaveRequest->id,
            ];
        }

        return [
            'title'      => 'Congé annulé par l\'administration',
            'body'       => sprintf(
                'Votre congé %s (%s) a été annulé par un responsable.',
                $this->leaveRequest->leaveType->name,
                $this->leaveRequest->period_label,
            ),
            'icon'       => '🚫',
            'type'       => 'leave_cancelled_by_admin',
            'action_url' => route('leaves.show', $this->leaveRequest->id),
            'leave_id'   => $this->leaveRequest->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $employee  = $this->leaveRequest->user;
        $leaveType = $this->leaveRequest->leaveType;
        $period    = $this->leaveRequest->period_label;

        if ($this->byEmployee) {
            return (new MailMessage)
                ->subject("Demande de congé annulée — {$employee->full_name}")
                ->greeting('Bonjour,')
                ->line("{$employee->full_name} a annulé sa demande de congé.")
                ->line("**Type :** {$leaveType->name}")
                ->line("**Période :** {$period}")
                ->line('Aucune action requise de votre part.')
                ->action('Voir les demandes', route('leaves.index'))
                ->salutation('L\'équipe SimpliRH');
        }

        return (new MailMessage)
            ->subject("Votre congé a été annulé")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line('Votre congé a été annulé par un responsable RH.')
            ->line("**Type :** {$leaveType->name}")
            ->line("**Période :** {$period}")
            ->line('Si vous avez des questions, contactez votre service RH.')
            ->action('Mes demandes', route('leaves.index'))
            ->salutation('L\'équipe SimpliRH');
    }
}
