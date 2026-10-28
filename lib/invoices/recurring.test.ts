import { describe, expect, it } from "vitest";

import { calculateNextRecurringDate } from "./recurring";

describe("calculateNextRecurringDate", () => {
  it.each([
    ["weekly", "2026-01-08T12:00:00.000Z"],
    ["monthly", "2026-02-01T12:00:00.000Z"],
    ["quarterly", "2026-04-01T12:00:00.000Z"],
    ["yearly", "2027-01-01T12:00:00.000Z"],
  ] as const)("calculates the next %s date", (interval, expected) => {
    const nextDate = calculateNextRecurringDate(
      new Date("2026-01-01T12:00:00.000Z"),
      interval,
    );

    expect(nextDate.toISOString()).toBe(expected);
  });
});

describe("recurring month boundaries", () => {
  it.each([
    ["2026-01-31T12:00:00.000Z", "monthly", "2026-02-28T12:00:00.000Z"],
    ["2028-02-29T12:00:00.000Z", "yearly", "2029-02-28T12:00:00.000Z"],
    ["2026-01-31T12:00:00.000Z", "quarterly", "2026-04-30T12:00:00.000Z"],
  ] as const)(
    "clamps %s + %s to the target month",
    (from, interval, expected) => {
      expect(
        calculateNextRecurringDate(new Date(from), interval).toISOString(),
      ).toBe(expected);
    },
  );
});
