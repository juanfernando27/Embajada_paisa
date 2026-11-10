<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ReservaConfirmada extends Mailable
{
    public function __construct(
        public string $cliente,
        public string $mesa,
        public string $inicio,
        public string $fin,
        public int $comensales,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reserva confirmada en La Embajada Paisa');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.reserva-confirmada');
    }
}
