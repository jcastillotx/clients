<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ClientWelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $portalUrl,
        public bool $passwordSetLinkSent = true,
        public ?string $temporaryPassword = null
    ) {}

    public function build(): self
    {
        return $this->subject('Welcome to '.config('app.name'))
            ->view('emails.client-welcome')
            ->text('emails.text.client-welcome');
    }
}
