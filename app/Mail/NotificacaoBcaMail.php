<?php

namespace App\Mail;

use App\Models\BcaOcorrencia;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificacaoBcaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly BcaOcorrencia $ocorrencia,
        public readonly ?string $adendo = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'BCA - Você foi mencionado no Boletim '.$this->ocorrencia->bca->numero,
        );
    }

    public function content(): Content
    {
        $bcaDownloadUrl = $this->ocorrencia->bca->url ? route('bca.pdf', $this->ocorrencia->bca->data->format('Y-m-d')) : null;

        return new Content(
            view: 'mail.notificacao-bca',
            with: [
                'bcaDownloadUrl' => $bcaDownloadUrl,
                'adendo' => $this->adendo,
            ],
        );
    }
}
