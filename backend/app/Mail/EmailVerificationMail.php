<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels;


    public $token;
    public $email;


    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
    }


    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify Your HelpDeskPro Account'
        );
    }


    public function content(): Content
    {
        return new Content(
            view: 'emails.verify-email'
        );
    }


    public function attachments(): array
    {
        return [];
    }
}