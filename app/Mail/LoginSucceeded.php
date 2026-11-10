<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class LoginSucceeded extends Mailable
{
    public function __construct(public string $accountName, public string $time) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Inicio de sesión en La Embajada Paisa');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.login-succeeded');
    }
}
