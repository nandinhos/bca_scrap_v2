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
        public readonly BcaOcorrencia $ocorrencia
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'BCA - Você foi mencionado no Boletim '.$this->ocorrencia->bca->numero,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.notificacao-bca',
        );
    }
}
