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
  webhookSecret: string;
  constructEvent: StripeConstructEvent;
  hasProcessedEvent: (eventId: string) => Promise<boolean>;
  onEvent: (event: StripeWebhookEvent) => Promise<void>;
};

export async function processStripeWebhookRequest({
  body,
  signature,
  webhookSecret,
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

  let event: StripeWebhookEvent;
  try {
    event = constructEvent(body, signature, webhookSecret);
  } catch {
    return { status: 400, payload: { error: "Invalid signature" } };
  }

  if (await hasProcessedEvent(event.id)) {
    return { status: 200, payload: { received: true, duplicate: true } };
  }

  await onEvent(event);
  return { status: 200, payload: { received: true } };
}
