<?php

namespace App\Mail;

use App\Models\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequestUpdatedNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Request $request,
        public ?string $oldStatus = null,
        public ?string $newStatus = null,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Request update · #' . $this->request->id)
            ->view('emails.request-updated', [
                'request' => $this->request,
                'oldStatus' => $this->oldStatus,
                'newStatus' => $this->newStatus,
            ])
            ->text('emails.text.request-updated', [
                'request' => $this->request,
                'oldStatus' => $this->oldStatus,
                'newStatus' => $this->newStatus,
            ]);
    }
}

