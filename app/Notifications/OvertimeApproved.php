<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\OvertimeEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifie l'employé que ses heures supplémentaires ont été approuvées.
 */
class OvertimeApproved extends Notification implements ShouldQueue
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
            'title'      => 'Heures sup. approuvées ✅',
            'body'       => sprintf(
                'Vos %s d\'heures sup. du %s (%s) ont été approuvées.',
                $this->entry->hours_label,
                $this->entry->date->translatedFormat('j M Y'),
                $this->entry->compensation_label,
            ),
            'icon'       => '✅',
            'type'       => 'overtime_approved',
            'action_url' => route('overtime.show', $this->entry->id),
            'overtime_id'=> $this->entry->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reviewer = $this->entry->reviewer;

        return (new MailMessage)
            ->subject("Vos heures supplémentaires ont été approuvées")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line('Bonne nouvelle ! Vos heures supplémentaires ont été approuvées.')
            ->line("**Date :** " . $this->entry->date->translatedFormat('l j F Y'))
            ->line("**Durée :** {$this->entry->hours_label} ({$this->entry->rate_label})")
            ->line("**Compensation :** {$this->entry->compensation_label}")
            ->when($reviewer, fn ($mail) =>
                $mail->line("**Approuvé par :** {$reviewer->full_name}")
            )
            ->when($this->entry->reviewer_comment, fn ($mail) =>
                $mail->line("**Commentaire :** {$this->entry->reviewer_comment}")
            )
            ->action('Voir le détail', route('overtime.show', $this->entry->id))
            ->salutation("L'équipe SimpliRH");
    }
}
