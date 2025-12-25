<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $setPasswordUrl,
        public string $roleLabel
    ) {}

    public function build(): self
    {
        return $this->subject('You’ve been invited · ' . config('app.name'))
            ->view('emails.user-invitation', [
                'user' => $this->user,
                'setPasswordUrl' => $this->setPasswordUrl,
                'roleLabel' => $this->roleLabel,
            ])
            ->text('emails.text.user-invitation', [
                'user' => $this->user,
                'setPasswordUrl' => $this->setPasswordUrl,
                'roleLabel' => $this->roleLabel,
            ]);
    }
}

