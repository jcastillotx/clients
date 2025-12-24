<x-app-layout>
    <x-slot name="header">Pay Invoice: {{ $invoice->invoice_number }}</x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Payment Details</h3>
                </div>
                <div class="card-body">
                    <!-- Invoice Summary -->
                    <div class="mb-4">
                        <dl class="row">
                            <dt class="col-sm-6">Invoice Number</dt>
                            <dd class="col-sm-6">{{ $invoice->invoice_number }}</dd>

                            <dt class="col-sm-6">Due Date</dt>
                            <dd class="col-sm-6">{{ $invoice->due_date->format('M d, Y') }}</dd>

                            <dt class="col-sm-6 h4">Amount Due</dt>
                            <dd class="col-sm-6 h4 text-primary">${{ number_format($invoice->balance_due, 2) }}</dd>
                        </dl>
                    </div>

                    <hr>

                    <!-- Stripe Payment Form -->
                    <form id="payment-form">
                        <div class="form-group">
                            <label for="card-element">Credit or Debit Card</label>
                            <div id="card-element" class="form-control" style="height: 40px; padding-top: 10px;">
                                <!-- Stripe Elements will be inserted here -->
                            </div>
                            <div id="card-errors" class="text-danger mt-2" role="alert"></div>
                        </div>

                        <button id="submit-button" type="submit" class="btn btn-success btn-lg btn-block">
                            <span id="button-text">
                                <i class="fas fa-lock mr-2"></i>
                                Pay ${{ number_format($invoice->balance_due, 2) }}
                            </span>
                            <span id="spinner" class="d-none">
                                <i class="fas fa-spinner fa-spin mr-2"></i>
                                Processing...
                            </span>
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <p class="text-muted small mb-0">
                            <i class="fas fa-lock mr-1"></i>
                            Your payment is secured with 256-bit SSL encryption.
                        </p>
                        <p class="text-muted small">
                            Powered by <strong>Stripe</strong>
                        </p>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Invoice
                    </a>
                </div>
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
                    color: '#32325d',
                    fontFamily: '"Inter", sans-serif',
                    '::placeholder': {
                        color: '#aab7c4'
                    }
                },
                invalid: {
                    color: '#dc3545',
                    iconColor: '#dc3545'
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
            buttonText.classList.add('d-none');
            spinner.classList.remove('d-none');

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
                buttonText.classList.remove('d-none');
                spinner.classList.add('d-none');
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
