<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\OvertimeEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifie l'employé que ses heures supplémentaires ont été refusées.
 */
class OvertimeRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly OvertimeEntry $entry) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'      => 'Heures sup. refusées',
            'body'       => sprintf(
                'Vos %s d\'heures sup. du %s ont été refusées.',
                $this->entry->hours_label,
                $this->entry->date->translatedFormat('j M Y'),
            ),
            'icon'       => '❌',
            'type'       => 'overtime_rejected',
            'action_url' => route('overtime.show', $this->entry->id),
            'overtime_id'=> $this->entry->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reviewer = $this->entry->reviewer;

        return (new MailMessage)
            ->subject("Vos heures supplémentaires n'ont pas été acceptées")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line('Votre déclaration d\'heures supplémentaires n\'a pas pu être validée.')
            ->line("**Date :** " . $this->entry->date->translatedFormat('l j F Y'))
            ->line("**Durée :** {$this->entry->hours_label}")
            ->when($reviewer, fn ($mail) =>
                $mail->line("**Refusé par :** {$reviewer->full_name}")
            )
            ->when($this->entry->reviewer_comment, fn ($mail) =>
                $mail->line("**Motif du refus :** {$this->entry->reviewer_comment}")
            )
            ->line('Si vous avez des questions, contactez votre responsable.')
            ->action('Voir le détail', route('overtime.show', $this->entry->id))
            ->salutation("L'équipe SimpliRH");
    }
}
