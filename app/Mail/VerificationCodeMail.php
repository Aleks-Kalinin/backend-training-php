<?php

namespace App\Mail;

use App\Enums\ChallengePurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Carries the one-time code to the user. Queued, per spec 00 section 4.
 *
 * The code lives in this object and in the rendered mail only. With
 * MAIL_MAILER=log it ends up in storage/logs/laravel.log, which is fine for
 * local development and must not be how a deployed environment is configured.
 */
class VerificationCodeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly ChallengePurpose $purpose,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your verification code',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.verification-code',
            with: [
                'code' => $this->code,
                'minutes' => (int) ceil(config('verification.otp.ttl') / 60),
            ],
        );
    }
}
