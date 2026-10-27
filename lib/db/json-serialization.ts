/** Drizzle 0.29 serializes JSON columns before handing parameters to postgres.js. */
export function serializeDrizzleJson(value: unknown): string {
  return typeof value === "string" ? value : JSON.stringify(value);
}

export const drizzleJsonTypes = {
  json: {
    to: 114,
    from: [114],
    serialize: serializeDrizzleJson,
    parse: (value: string): unknown => JSON.parse(value),
  },
  jsonb: {
    to: 3802,
    from: [3802],
    serialize: serializeDrizzleJson,
    parse: (value: string): unknown => JSON.parse(value),
  },
};
