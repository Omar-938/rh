<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Payslip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifie l'employé qu'un bulletin de paie est disponible.
 */
class PayslipDistributed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Payslip $payslip) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'      => 'Bulletin de paie disponible 💰',
            'body'       => sprintf(
                'Votre bulletin de paie de %s est disponible. Vous pouvez le télécharger dès maintenant.',
                $this->payslip->period_label,
            ),
            'icon'       => '💰',
            'type'       => 'payslip_distributed',
            'action_url' => route('payslips.index'),
            'payslip_id' => $this->payslip->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Votre bulletin de paie {$this->payslip->period_label} est disponible")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("Votre bulletin de paie de **{$this->payslip->period_label}** vient d'être mis à votre disposition.")
            ->line('Connectez-vous à votre espace SimpliRH pour le consulter et le télécharger.')
            ->action('Voir mes bulletins', route('payslips.index'))
            ->salutation("L'équipe SimpliRH");
    }
}
