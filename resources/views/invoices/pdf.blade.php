@php
    $brand = $brand ?? config('client-portal.invoice.branding', []);
    $template = $invoice->template ?: 'classic';
    $templates = config('client-portal.invoice.templates', []);
    if (!array_key_exists($template, $templates)) {
        $template = 'classic';
    }
@endphp

@include('invoices.templates.' . $template, ['invoice' => $invoice, 'brand' => $brand])
