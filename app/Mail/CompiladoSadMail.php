<?php

namespace App\Mail;

use App\Models\Bca;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class CompiladoSadMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Bca $bca,
        public readonly Collection $ocorrencias
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "BCA nº {$this->bca->numero} de {$this->bca->data->format('d/m/Y')} - Compilado GAC-PAC",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.compilado-sad',
        );
    }
}
