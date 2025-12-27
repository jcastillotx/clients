<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentReceivedNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Payment $payment) {}

    public function build(): self
    {
        $invoiceNumber = $this->payment->invoice?->invoice_number ?? 'Invoice';

        return $this
            ->subject('Payment receipt · '.$invoiceNumber)
            ->view('emails.payment-received', ['payment' => $this->payment])
            ->text('emails.text.payment-received', ['payment' => $this->payment]);
    }
}
