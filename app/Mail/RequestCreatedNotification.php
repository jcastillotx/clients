<?php

namespace App\Mail;

use App\Models\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequestCreatedNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Request $request)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('New request created · #' . $this->request->id)
            ->view('emails.request-created', ['request' => $this->request])
            ->text('emails.text.request-created', ['request' => $this->request]);
    }
}

