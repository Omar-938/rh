<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\SignatureStatus;
use App\Models\Signature;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentSigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Signature $signature) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $document = $this->signature->document;
        $signer   = $this->signature->user;

        if ($this->signature->status === SignatureStatus::Signed) {
            return (new MailMessage)
                ->subject("Document signé : {$document->name}")
                ->greeting("Bonjour {$notifiable->first_name},")
                ->line("{$signer->full_name} a signé le document **{$document->name}**.")
                ->line("Date de signature : {$this->signature->signed_at?->translatedFormat('j M Y à H:i')}")
                ->action('Voir les documents', route('documents.index'))
                ->salutation('L\'équipe SimpliRH');
        }

        // Refus
        return (new MailMessage)
            ->subject("Signature refusée : {$document->name}")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("{$signer->full_name} a **refusé** de signer le document **{$document->name}**.")
            ->when(
                $this->signature->declined_reason,
                fn ($m) => $m->line("Motif : {$this->signature->declined_reason}")
            )
            ->action('Voir les documents', route('documents.index'))
            ->salutation('L\'équipe SimpliRH');
    }

    public function toArray(object $notifiable): array
    {
        $isSigned = $this->signature->status === SignatureStatus::Signed;
        $signer   = $this->signature->user;
        $document = $this->signature->document;

        return [
            'type'         => $isSigned ? 'document_signed' : 'signature_declined',
            'message'      => $isSigned
                ? "{$signer->full_name} a signé le document « {$document->name} »."
                : "{$signer->full_name} a refusé de signer « {$document->name} ».",
            'document_id'  => $document->id,
            'signature_id' => $this->signature->id,
            'link'         => route('documents.index'),
        ];
    }
}
