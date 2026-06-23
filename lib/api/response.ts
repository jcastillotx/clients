import { NextResponse } from "next/server";
import type { z } from "zod";
import type { PaginationMeta } from "@/lib/api/pagination";
import { getRequestId, REQUEST_ID_HEADER } from "@/lib/api/request-context";

export type ApiErrorCode =
  | "UNAUTHORIZED"
  | "FORBIDDEN"
  | "NOT_FOUND"
  | "VALIDATION_ERROR"
  | "RATE_LIMITED"
  | "FEATURE_NOT_ENABLED"
  | "BAD_REQUEST"
  | "INTERNAL_ERROR"
  | "SERVICE_UNAVAILABLE";

export type ApiErrorBody = {
  success: false;
  error: {
    code: ApiErrorCode;
    message: string;
    details?: unknown;
  };
  /** Duplicate of error.message for simple client access during migration. */
  message: string;
  requestId: string;
};

export type ApiSuccessBody<T> = {
  success: true;
  data: T;
  requestId: string;
  pagination?: PaginationMeta;
};

type ApiErrorOptions = {
  status: number;
  code: ApiErrorCode;
  message: string;
  details?: unknown;
  headers?: HeadersInit;
};

type ApiSuccessOptions = {
  status?: number;
  pagination?: PaginationMeta;
  headers?: HeadersInit;
  /** Backward-compatible top-level fields merged alongside `data`. */
  extra?: Record<string, unknown>;
};

export function apiError(request: Request, options: ApiErrorOptions): NextResponse {
  const requestId = getRequestId(request);
  const headers = new Headers(options.headers);
  headers.set(REQUEST_ID_HEADER, requestId);

  const body: ApiErrorBody = {
    success: false,
    error: {
      code: options.code,
      message: options.message,
      ...(options.details !== undefined ? { details: options.details } : {}),
    },
    message: options.message,
    requestId,
  };

  return NextResponse.json(body, {
    status: options.status,
    headers,
  });
}

export function apiSuccess<T>(
  request: Request,
  data: T,
  options: ApiSuccessOptions = {},
): NextResponse {
  const requestId = getRequestId(request);
  const headers = new Headers(options.headers);
  headers.set(REQUEST_ID_HEADER, requestId);

  const body: ApiSuccessBody<T> & Record<string, unknown> = {
    success: true,
    data,
    requestId,
    ...(options.pagination ? { pagination: options.pagination } : {}),
    ...(options.extra ?? {}),
  };

  return NextResponse.json(body, {
    status: options.status ?? 200,
    headers,
  });
}

/** Map common HTTP statuses to stable API error codes. */
export function errorCodeFromStatus(status: number): ApiErrorCode {
  switch (status) {
    case 400:
      return "BAD_REQUEST";
    case 401:
      return "UNAUTHORIZED";
    case 403:
      return "FORBIDDEN";
    case 404:
      return "NOT_FOUND";
    case 429:
      return "RATE_LIMITED";
    case 501:
      return "FEATURE_NOT_ENABLED";
    case 503:
      return "SERVICE_UNAVAILABLE";
    default:
      return status >= 500 ? "INTERNAL_ERROR" : "BAD_REQUEST";
  }
}

/** Read a human-readable message from old or new API error payloads. */
export function extractApiErrorMessage(
  payload: unknown,
  fallback = "Request failed",
): string {
  if (!payload || typeof payload !== "object") {
    return fallback;
  }

  const record = payload as Record<string, unknown>;

  if (typeof record.message === "string" && record.message.trim()) {
    return record.message;
  }

  const err = record.error;
  if (typeof err === "string") {
    return err;
  }

  if (
    err &&
    typeof err === "object" &&
    typeof (err as { message?: unknown }).message === "string"
  ) {
    return (err as { message: string }).message;
  }

  return fallback;
}

export function apiUnauthorized(
  request: Request,
  message = "Unauthorized",
): NextResponse {
  return apiError(request, {
    status: 401,
    code: "UNAUTHORIZED",
    message,
  });
}

export function apiForbidden(
  request: Request,
  message = "Permission denied",
): NextResponse {
  return apiError(request, {
    status: 403,
    code: "FORBIDDEN",
    message,
  });
}

export function apiNotFound(request: Request, message = "Not found"): NextResponse {
  return apiError(request, {
    status: 404,
    code: "NOT_FOUND",
    message,
  });
}

export function apiValidationError(
  request: Request,
  zodError: z.ZodError,
): NextResponse {
  return apiError(request, {
    status: 400,
    code: "VALIDATION_ERROR",
    message: "Validation error",
    details: zodError.errors,
  });
}

export function apiInternalError(
  request: Request,
  message: string,
): NextResponse {
  return apiError(request, {
    status: 500,
    code: "INTERNAL_ERROR",
    message,
  });
}
