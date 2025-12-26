<x-app-layout>
    <x-slot name="header">Invoice</x-slot>

    <livewire:invoices.invoice-show :invoice="$invoice" />
</x-app-layout>
