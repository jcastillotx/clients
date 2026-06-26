type StripeWebhookEvent = {
  id: string;
  type: string;
  data: {
    object: unknown;
  };
};

type StripeConstructEvent = (
  body: string,
  signature: string,
  webhookSecret: string,
) => StripeWebhookEvent;

type ProcessStripeWebhookRequestParams = {
  body: string;
  signature: string | null;
  webhookSecret?: string;
  webhookSecrets?: string[];
  constructEvent: StripeConstructEvent;
  hasProcessedEvent: (eventId: string) => Promise<boolean>;
  onEvent: (event: StripeWebhookEvent) => Promise<void>;
};

export async function processStripeWebhookRequest({
  body,
  signature,
  webhookSecret,
  webhookSecrets,
  constructEvent,
  hasProcessedEvent,
  onEvent,
}: ProcessStripeWebhookRequestParams): Promise<{
  status: number;
  payload: Record<string, unknown>;
}> {
  if (!signature) {
    return { status: 400, payload: { error: "Missing signature" } };
  }

  const candidateSecrets = webhookSecrets ?? (webhookSecret ? [webhookSecret] : []);
  if (candidateSecrets.length === 0) {
    return { status: 503, payload: { error: "Stripe webhook is not configured" } };
  }

  let event: StripeWebhookEvent | null = null;
  for (const candidateSecret of candidateSecrets) {
    try {
      event = constructEvent(body, signature, candidateSecret);
      break;
    } catch {
      // Try the next configured webhook secret. Stripe endpoints may be client-scoped.
    }
  }

  if (!event) {
    return { status: 400, payload: { error: "Invalid signature" } };
  }

  if (await hasProcessedEvent(event.id)) {
    return { status: 200, payload: { received: true, duplicate: true } };
  }

  await onEvent(event);
  return { status: 200, payload: { received: true } };
}
