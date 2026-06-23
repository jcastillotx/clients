import { vi } from "vitest";

type QueryResult = {
  data?: unknown;
  error?: { code?: string; message?: string } | null;
};

export function createSupabaseQueryChain(result: QueryResult = { data: [], error: null }) {
  const chain = {
    select: vi.fn().mockReturnThis(),
    insert: vi.fn().mockReturnThis(),
    update: vi.fn().mockReturnThis(),
    eq: vi.fn().mockReturnThis(),
    is: vi.fn().mockReturnThis(),
    order: vi.fn().mockReturnThis(),
    limit: vi.fn().mockReturnThis(),
    maybeSingle: vi.fn().mockResolvedValue(result),
    single: vi.fn().mockResolvedValue(result),
    then: undefined as unknown,
  };

  chain.then = (resolve: (value: QueryResult) => unknown) =>
    Promise.resolve(result).then(resolve);

  return chain;
}

export function createMockSupabaseClient(options: {
  user?: { id: string; email?: string } | null;
  authError?: Error | null;
  fromResults?: Record<string, QueryResult>;
}) {
  const defaultUser = options.user ?? null;

  return {
    auth: {
      getUser: vi.fn().mockResolvedValue({
        data: { user: defaultUser },
        error: options.authError ?? null,
      }),
    },
    from: vi.fn((table: string) => {
      const result = options.fromResults?.[table] ?? { data: [], error: null };
      return createSupabaseQueryChain(result);
    }),
  };
}
