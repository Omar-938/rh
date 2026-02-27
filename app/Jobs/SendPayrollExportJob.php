<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\PayrollExportMail;
use App\Models\PayrollExport;
use App\Models\User;
use App\Services\PayrollExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendPayrollExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60;  // secondes entre les tentatives

    public function __construct(
        public readonly PayrollExport $export,
        public readonly array         $emails,
        public readonly string        $format,
        public readonly User          $sender,
    ) {}

    public function handle(PayrollExportService $service): void
    {
        // Recharger les relations nécessaires
        $this->export->loadMissing(['company', 'validatedBy', 'lines.user.department']);

        $lines    = $this->export->lines->sortBy('id');
        $content  = $service->generateContent($this->export, $lines, $this->format);
        $filename = $service->getFilename($this->export, $this->format);
        $mimeType = $service->getMimeType($this->format);

        $mailable = new PayrollExportMail(
            export:      $this->export,
            fileContent: $content,
            filename:    $filename,
            mimeType:    $mimeType,
            sender:      $this->sender,
        );

        // Envoi aux destinataires + copie à l'expéditeur
        $recipients = array_unique($this->emails);
        $cc         = in_array($this->sender->email, $recipients, true) ? [] : [$this->sender->email];

        Mail::to($recipients)
            ->when(count($cc) > 0, fn ($m) => $m->cc($cc))
            ->send($mailable);
    }
}
