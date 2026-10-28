import {validateFormRequest} from "@/lib/validations/form-request";
import { extractApiErrorMessage } from "@/lib/api/response";
import type { ApiErrorBody, ApiSuccessBody } from "@/lib/api/response";

export class ApiClientError extends Error {
  readonly status: number;
  readonly payload: unknown;
  readonly requestId?: string;

  constructor(message: string, status: number, payload: unknown, requestId?: string) {
    super(message);
    this.name = "ApiClientError";
    this.status = status;
    this.payload = payload;
    this.requestId = requestId;
  }
}

export type FetchApiOptions = {
  /** Message used when the API body has no readable error text. */
  fallbackMessage?: string;
  /** Return the full JSON body (including pagination/extra fields) instead of unwrapping `data`. */
  raw?: boolean;
};

/** Unwrap `data` from standardized success payloads; pass through legacy shapes. */
export function unwrapApiData<T>(payload: unknown): T {
  if (!payload || typeof payload !== "object") {
    return payload as T;
  }

  const record = payload as Record<string, unknown>;
  if ("data" in record) {
    return record.data as T;
  }

  return payload as T;
}

function readRequestId(payload: unknown): string | undefined {
  if (!payload || typeof payload !== "object") {
    return undefined;
  }

  const requestId = (payload as { requestId?: unknown }).requestId;
  return typeof requestId === "string" ? requestId : undefined;
}

/**
 * Browser/client fetch wrapper for app API routes.
 * Throws `ApiClientError` on non-2xx responses with a human-readable message.
 */
export async function fetchApi<T>(
  input: RequestInfo | URL,
  init?: RequestInit,
  options: FetchApiOptions = {},
): Promise<T> {
  if (typeof input === "string" && input.startsWith("/api/") && typeof init?.body === "string" && ["POST", "PATCH", "PUT"].includes(init.method?.toUpperCase() || "")) {
    let data: unknown;
    try { data = JSON.parse(init.body); } catch { throw new ApiClientError("Send valid form data.", 400, {}); }
    const result = validateFormRequest(input, init.method!.toUpperCase(), data);
    if (result && !result.success) {
      const issue = result.error.issues[0];
      const field = issue.path.map(part => typeof part === "number" ? String(part + 1) : String(part).replace(/([a-z0-9])([A-Z])/g, "$1 $2").replace(/_/g, " ").toLowerCase()).join(" → ");
      throw new ApiClientError(`${field ? field + ": " : ""}${issue.message}`, 400, {error: {details: result.error.issues}});
    }
  }
  const response = await fetch(input, init);
  const payload = await response.json().catch(() => ({}));

  if (!response.ok) {
    throw new ApiClientError(
      extractApiErrorMessage(payload, options.fallbackMessage ?? "Request failed"),
      response.status,
      payload,
      readRequestId(payload),
    );
  }

  if (options.raw) {
    return payload as T;
  }

  return unwrapApiData<T>(payload);
}

/**
 * Fetch a binary response (PDF, image, etc.). Throws `ApiClientError` on non-2xx JSON errors.
 */
export async function fetchBinary(
  input: RequestInfo | URL,
  init?: RequestInit,
  options: FetchApiOptions = {},
): Promise<Blob> {
  const response = await fetch(input, init);

  if (!response.ok) {
    const payload = await response.json().catch(() => ({}));
    throw new ApiClientError(
      extractApiErrorMessage(payload, options.fallbackMessage ?? "Request failed"),
      response.status,
      payload,
      readRequestId(payload),
    );
  }

  return response.blob();
}

export type ApiSuccessResponse<T> = ApiSuccessBody<T> & Record<string, unknown>;
export type ApiErrorResponse = ApiErrorBody;
