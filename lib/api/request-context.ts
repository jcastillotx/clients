export const REQUEST_ID_HEADER = "x-request-id";

/** Read the correlation ID from an incoming request (API route or middleware). */
export function getRequestId(request: Request): string {
  return request.headers.get(REQUEST_ID_HEADER) ?? crypto.randomUUID();
}

/** Resolve or create a correlation ID for middleware forwarding. */
export function resolveRequestId(request: Request): string {
  const existing = request.headers.get(REQUEST_ID_HEADER)?.trim();
  if (existing) {
    return existing;
  }

  return crypto.randomUUID();
}

/** Attach the correlation ID to outgoing response headers. */
export function withRequestIdHeader(
  headers: Headers,
  requestId: string,
): Headers {
  headers.set(REQUEST_ID_HEADER, requestId);
  return headers;
}
