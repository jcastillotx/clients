<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public string $kind // due_soon|overdue
    ) {}

    public function build(): self
    {
        $subject = match ($this->kind) {
            'due_soon' => 'Invoice due soon · '.$this->invoice->invoice_number,
            'overdue' => 'Invoice overdue · '.$this->invoice->invoice_number,
            default => 'Invoice reminder · '.$this->invoice->invoice_number,
        };

        return $this->subject($subject)
            ->view('emails.invoice-reminder', ['invoice' => $this->invoice, 'kind' => $this->kind])
            ->text('emails.text.invoice-reminder', ['invoice' => $this->invoice, 'kind' => $this->kind]);
    }
}
