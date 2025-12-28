<x-app-layout>
    <x-slot name="header">Pay Invoice: {{ $invoice->invoice_number }}</x-slot>

    <div class="max-w-lg mx-auto">
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h2 class="text-base font-semibold text-slate-900">Payment Details</h2>
            </div>

            <div class="p-6">
                <!-- Invoice Summary -->
                <div class="rounded-xl bg-slate-50 border border-slate-200 p-4 mb-6">
                    <dl class="space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-sm text-slate-500">Invoice Number</dt>
                            <dd class="text-sm font-medium text-slate-900">{{ $invoice->invoice_number }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-slate-500">Due Date</dt>
                            <dd class="text-sm font-medium text-slate-900">{{ $invoice->due_date->format('M d, Y') }}</dd>
                        </div>
                        <div class="flex justify-between pt-3 border-t border-slate-200">
                            <dt class="text-lg font-semibold text-slate-900">Amount Due</dt>
                            <dd class="text-lg font-bold text-slate-900">${{ number_format($invoice->balance_due, 2) }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Stripe Payment Form -->
                <form id="payment-form" class="space-y-5">
                    <div>
                        <label for="card-element" class="block text-xs font-semibold text-slate-600 mb-1.5">Credit or Debit Card</label>
                        <div id="card-element" class="rounded-xl border border-slate-300 px-4 py-3 bg-white focus-within:border-slate-900 focus-within:ring-1 focus-within:ring-slate-900 transition-all">
                            <!-- Stripe Elements will be inserted here -->
                        </div>
                        <div id="card-errors" class="mt-1.5 text-xs font-medium text-rose-600" role="alert"></div>
                    </div>

                    <button id="submit-button" type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors flex items-center justify-center gap-2">
                        <span id="button-text" class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                            </svg>
                            Pay ${{ number_format($invoice->balance_due, 2) }}
                        </span>
                        <span id="spinner" class="hidden flex items-center gap-2">
                            <svg class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            Processing...
                        </span>
                    </button>
                </form>

                <!-- Security Notice -->
                <div class="mt-6 text-center">
                    <div class="flex items-center justify-center gap-2 text-sm text-slate-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                        <span>Secured with 256-bit SSL encryption</span>
                    </div>
                    <p class="mt-2 text-xs text-slate-400">
                        Powered by <span class="font-medium">Stripe</span>
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                <a href="{{ route('invoices.show', $invoice) }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                    </svg>
                    Back to Invoice
                </a>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        const stripe = Stripe('{{ $stripeKey }}');
        const elements = stripe.elements();
        const cardElement = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#0f172a',
                    fontFamily: '"Inter", system-ui, sans-serif',
                    fontSmoothing: 'antialiased',
                    '::placeholder': {
                        color: '#94a3b8'
                    }
                },
                invalid: {
                    color: '#e11d48',
                    iconColor: '#e11d48'
                }
            }
        });

        cardElement.mount('#card-element');

        cardElement.on('change', function(event) {
            const displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });

        const form = document.getElementById('payment-form');
        const submitButton = document.getElementById('submit-button');
        const buttonText = document.getElementById('button-text');
        const spinner = document.getElementById('spinner');

        form.addEventListener('submit', async function(event) {
            event.preventDefault();
            
            submitButton.disabled = true;
            buttonText.classList.add('hidden');
            spinner.classList.remove('hidden');

            const { paymentIntent, error } = await stripe.confirmCardPayment(
                '{{ $clientSecret }}',
                {
                    payment_method: {
                        card: cardElement,
                        billing_details: {
                            name: '{{ auth()->user()->name }}',
                            email: '{{ auth()->user()->email }}'
                        }
                    }
                }
            );

            if (error) {
                const displayError = document.getElementById('card-errors');
                displayError.textContent = error.message;
                submitButton.disabled = false;
                buttonText.classList.remove('hidden');
                spinner.classList.add('hidden');
            } else {
                // Payment succeeded - submit to server
                const processForm = document.createElement('form');
                processForm.method = 'POST';
                processForm.action = '{{ route('payments.process', $invoice) }}';

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = '{{ csrf_token() }}';
                processForm.appendChild(csrfInput);

                const paymentIntentInput = document.createElement('input');
                paymentIntentInput.type = 'hidden';
                paymentIntentInput.name = 'payment_intent_id';
                paymentIntentInput.value = paymentIntent.id;
                processForm.appendChild(paymentIntentInput);

                document.body.appendChild(processForm);
                processForm.submit();
            }
        });
    </script>
    @endpush
</x-app-layout>
