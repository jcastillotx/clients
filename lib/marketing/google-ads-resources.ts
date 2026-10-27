/** Google CustomerClient names retain the manager through which access was granted. */
export function googleAdsResourceTarget(resource: string) {
  const direct = /^customers\/(\d+)$/.exec(resource);
  if (direct) return { customerId: direct[1], loginCustomerId: null };
  const managed = /^customers\/(\d+)\/customerClients\/(\d+)$/.exec(resource);
  if (managed) return { customerId: managed[2], loginCustomerId: managed[1] };
  return null;
}
