export async function readJson<T = unknown>(response: Response): Promise<T> {
  return (await response.json()) as T;
}

export function jsonRequest(
  url: string,
  body: unknown,
  init: RequestInit = {},
): Request {
  return new Request(url, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      ...(init.headers ?? {}),
    },
    body: JSON.stringify(body),
    ...init,
  });
}
