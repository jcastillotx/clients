<?php

namespace App\Mail;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContractExpiringNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Contract $contract, public int $daysRemaining = 30)
    {
    }

    public function build(): self
    {
        return $this
            ->subject("Contract expiring soon · {$this->contract->title}")
            ->view('emails.contract-expiring', [
                'contract' => $this->contract,
                'daysRemaining' => $this->daysRemaining,
            ])
            ->text('emails.text.contract-expiring', [
                'contract' => $this->contract,
                'daysRemaining' => $this->daysRemaining,
            ]);
    }
}

