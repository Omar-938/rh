<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\OvertimeEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifie les admins / managers qu'une déclaration d'heures sup. a été soumise.
 */
class OvertimeSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly OvertimeEntry $entry) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $source = $this->entry->source === 'auto' ? 'détectées automatiquement' : 'déclarées';
        return [
            'title'      => 'Heures sup. à valider',
            'body'       => sprintf(
                '%s : %s %s le %s (%s).',
                $this->entry->user->full_name,
                $this->entry->hours_label,
                $source,
                $this->entry->date->translatedFormat('j M Y'),
                $this->entry->rate_label,
            ),
            'icon'       => '⏱️',
            'type'       => 'overtime_submitted',
            'action_url' => route('overtime.show', $this->entry->id),
            'overtime_id'=> $this->entry->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $employee = $this->entry->user;
        $source   = $this->entry->source === 'auto'
            ? 'détectées automatiquement par la pointeuse'
            : 'déclarées manuellement';

        return (new MailMessage)
            ->subject("Heures sup. à valider — {$employee->full_name}")
            ->greeting('Bonjour,')
            ->line("{$employee->full_name} a des heures supplémentaires {$source} nécessitant votre validation.")
            ->line("**Date :** " . $this->entry->date->translatedFormat('l j F Y'))
            ->line("**Durée :** {$this->entry->hours_label}")
            ->line("**Taux :** {$this->entry->rate_label}")
            ->when($this->entry->reason, fn ($mail) =>
                $mail->line("**Motif :** {$this->entry->reason}")
            )
            ->action('Valider la déclaration', route('overtime.show', $this->entry->id))
            ->salutation("L'équipe SimpliRH");
    }
}
