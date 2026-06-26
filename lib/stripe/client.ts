import Stripe from "stripe";

let _stripe: Stripe | null = null;

export function createStripeClient(secretKey: string): Stripe {
  return new Stripe(secretKey, {
    apiVersion: "2024-12-18.acacia" as any,
    typescript: true,
  });
}

export function getStripe(secretKey?: string): Stripe {
  const resolvedSecretKey = secretKey ?? process.env.STRIPE_SECRET_KEY;

  if (!resolvedSecretKey) {
    throw new Error("Stripe payments are not configured.");
  }

  if (resolvedSecretKey !== process.env.STRIPE_SECRET_KEY) {
    return createStripeClient(resolvedSecretKey);
  }

  if (!_stripe) {
    _stripe = createStripeClient(resolvedSecretKey);
  }
  return _stripe;
}

/** @deprecated Use getStripe() instead for lazy initialization */
export const stripe = new Proxy({} as Stripe, {
  get(_target, prop) {
    return (getStripe() as any)[prop];
  },
});
