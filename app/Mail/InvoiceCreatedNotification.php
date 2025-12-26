<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceCreatedNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('New invoice · ' . $this->invoice->invoice_number)
            ->view('emails.invoice-created', ['invoice' => $this->invoice])
            ->text('emails.text.invoice-created', ['invoice' => $this->invoice]);
    }
}

