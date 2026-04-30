<?php

namespace App\Mail;

use App\Models\Bca;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

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
        $bcaUrl = $this->bca->url
            ? Storage::disk('public')->url($this->bca->url)
            : null;

        return new Content(
            view: 'mail.compilado-sad',
            with: ['bcaDownloadUrl' => $bcaUrl],
        );
    }
}
