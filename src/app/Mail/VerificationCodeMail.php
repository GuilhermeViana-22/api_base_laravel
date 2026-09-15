<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail com o código de verificação do cadastro.
 * Vai pela fila (worker) para a resposta da API não esperar o Resend.
 */
class VerificationCodeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $name,
        public readonly string $code,
        public readonly int $ttlMinutes,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->code} é o seu código de verificação da Univesp",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verification-code',
            text: 'emails.verification-code-text',
        );
    }
}
