<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Signature;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SignatureRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Signature $signature,
        private readonly User $requester,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $signingUrl = route('signature.show', $this->signature->token);
        $document   = $this->signature->document;
        $expiresDays = (int) now()->diffInDays($this->signature->expires_at);

        return (new MailMessage)
            ->subject("Signature requise : {$document->name}")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("{$this->requester->full_name} vous demande de signer le document **{$document->name}**.")
            ->line("Vous avez {$expiresDays} jours pour signer ce document.")
            ->action('Signer le document', $signingUrl)
            ->line('Si vous ne reconnaissez pas cette demande, ignorez cet email.')
            ->salutation('L\'équipe SimpliRH');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'signature_requested',
            'message'      => "Signature requise pour le document « {$this->signature->document->name} ».",
            'document_id'  => $this->signature->document_id,
            'signature_id' => $this->signature->id,
            'token'        => $this->signature->token,
            'link'         => route('signature.show', $this->signature->token),
        ];
    }
}
