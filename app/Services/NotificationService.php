<?php

namespace App\Services;

use App\Mail\ContractExpiringNotification;
use App\Mail\InvoiceCreatedNotification;
use App\Mail\PaymentReceivedNotification;
use App\Mail\RequestCreatedNotification;
use App\Mail\RequestUpdatedNotification;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Request;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * @param  'created'|'updated'|'status_changed'  $type
     */
    public function sendRequestNotification(Request $request, string $type, ?string $oldStatus = null, ?string $newStatus = null): void
    {
        if ($type === 'created') {
            $recipients = User::query()->role(['super_admin', 'admin'])->get();
            $this->queueToUsers($recipients, new RequestCreatedNotification($request));
            return;
        }

        if ($type === 'status_changed') {
            $request->loadMissing('client');

            $recipients = User::query()
                ->where('client_id', $request->client_id)
                ->role(['client'])
                ->get();

            $this->queueToUsers($recipients, new RequestUpdatedNotification($request, $oldStatus, $newStatus));
            return;
        }

        // generic updated (no status details)
        $recipients = User::query()
            ->where('client_id', $request->client_id)
            ->role(['client'])
            ->get();

        $this->queueToUsers($recipients, new RequestUpdatedNotification($request));
    }

    /**
     * @param  'created'  $type
     */
    public function sendInvoiceNotification(Invoice $invoice, string $type): void
    {
        if ($type !== 'created') {
            return;
        }

        $invoice->loadMissing('client');

        $recipients = User::query()
            ->where('client_id', $invoice->client_id)
            ->role(['client'])
            ->get();

        $this->queueToUsers($recipients, new InvoiceCreatedNotification($invoice));
    }

    public function sendPaymentNotification(Payment $payment): void
    {
        $payment->loadMissing('invoice', 'invoice.client');

        $invoice = $payment->invoice;
        if (!$invoice) {
            return;
        }

        $recipients = User::query()
            ->where('client_id', $invoice->client_id)
            ->role(['client'])
            ->get();

        $this->queueToUsers($recipients, new PaymentReceivedNotification($payment));
    }

    public function sendContractExpiringNotification(Contract $contract, int $daysRemaining = 30): void
    {
        $contract->loadMissing('client');

        $recipients = User::query()
            ->where('client_id', $contract->client_id)
            ->role(['client'])
            ->get();

        $this->queueToUsers($recipients, new ContractExpiringNotification($contract, $daysRemaining));
    }

    /**
     * Queue a mailable to a list of users.
     */
    protected function queueToUsers(Collection $users, $mailable): void
    {
        if ($users->isEmpty()) {
            return;
        }

        foreach ($users as $user) {
            if (!$user->email) {
                continue;
            }

            Mail::to($user->email)->queue(clone $mailable);
        }
    }
}

