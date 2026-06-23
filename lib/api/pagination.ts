export type PaginationParams = {
  limit: number;
  offset: number;
};

export type PaginationMeta = PaginationParams & {
  total: number | null;
  hasMore: boolean;
};

type ParsePaginationOptions = {
  defaultLimit?: number;
  maxLimit?: number;
};

export function parsePaginationSearchParams(
  searchParams: URLSearchParams,
  options: ParsePaginationOptions = {},
): PaginationParams {
  const defaultLimit = options.defaultLimit ?? 50;
  const maxLimit = options.maxLimit ?? 100;

  const parsedLimit = Number.parseInt(searchParams.get("limit") ?? "", 10);
  const parsedOffset = Number.parseInt(searchParams.get("offset") ?? "", 10);

  const limit = Number.isFinite(parsedLimit)
    ? Math.min(Math.max(parsedLimit, 1), maxLimit)
    : defaultLimit;
  const offset = Number.isFinite(parsedOffset) ? Math.max(parsedOffset, 0) : 0;

  return { limit, offset };
}

export function buildPaginationMeta(
  params: PaginationParams,
  total: number | null,
  returnedCount: number,
): PaginationMeta {
  return {
    ...params,
    total,
    hasMore:
      total !== null
        ? params.offset + returnedCount < total
        : returnedCount === params.limit,
  };
}
