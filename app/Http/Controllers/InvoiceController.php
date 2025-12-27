<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    protected function invoiceBranding(): array
    {
        return (array) config('client-portal.invoice.branding', []);
    }

    /**
     * Display a listing of the invoices.
     */
    public function index(): View
    {
        return view('invoices.index');
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice): View
    {
        $this->authorizeClientAccess($invoice);

        $invoice->load(['client', 'items', 'payments']);

        return view('invoices.show', compact('invoice'));
    }

    /**
     * Download the invoice as PDF.
     */
    public function download(Invoice $invoice): StreamedResponse
    {
        $this->authorizeClientAccess($invoice);

        $invoice->load(['client', 'items']);

        // Generate PDF if not exists
        if (! $invoice->pdf_path || ! Storage::disk('invoices')->exists($invoice->pdf_path)) {
            $this->generatePdf($invoice);
        }

        ActivityLog::log(
            "Downloaded invoice: {$invoice->invoice_number}",
            $invoice,
            null,
            'downloaded',
            'invoices'
        );

        return Storage::disk('invoices')->download(
            $invoice->pdf_path,
            $invoice->invoice_number.'.pdf'
        );
    }

    /**
     * View invoice as PDF in browser.
     */
    public function pdf(Invoice $invoice)
    {
        $this->authorizeClientAccess($invoice);

        $invoice->load(['client', 'items']);

        $brand = $this->invoiceBranding();
        $pdf = Pdf::loadView('invoices.pdf', compact('invoice', 'brand'));

        return $pdf->stream($invoice->invoice_number.'.pdf');
    }

    /**
     * Generate and store PDF for invoice.
     */
    protected function generatePdf(Invoice $invoice): void
    {
        $brand = $this->invoiceBranding();
        $pdf = Pdf::loadView('invoices.pdf', compact('invoice', 'brand'));

        $filename = $invoice->invoice_number.'.pdf';
        $path = 'generated/'.$filename;

        Storage::disk('invoices')->put($path, $pdf->output());

        $invoice->update(['pdf_path' => $path]);
    }

    /**
     * Authorize that the current user can access this invoice.
     */
    protected function authorizeClientAccess(Invoice $invoice): void
    {
        $user = auth()->user();

        if ($user->isClient() && $invoice->client_id !== $user->client_id) {
            abort(403, 'You do not have access to this invoice.');
        }
    }
}
