<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\PayrollExport;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PayrollExportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly PayrollExport $export,
        public readonly string        $fileContent,
        public readonly string        $filename,
        public readonly string        $mimeType,
        public readonly User          $sender,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                'SimpliRH - Variables de paie %s - %s',
                $this->export->company->name ?? '',
                $this->export->period_label,
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payroll_export',
            with: [
                'export'   => $this->export,
                'sender'   => $this->sender,
                'filename' => $this->filename,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => $this->fileContent,
                $this->filename,
            )->withMime($this->mimeType),
        ];
    }
}
