export const recurringIntervals = ["weekly", "monthly", "quarterly", "yearly"] as const;

export type RecurringInterval = (typeof recurringIntervals)[number];

export function calculateNextRecurringDate(
  from: Date,
  interval: RecurringInterval,
): Date {
  const nextDate = new Date(from);

  switch (interval) {
    case "weekly":
      nextDate.setUTCDate(nextDate.getUTCDate() + 7);
      break;
    case "monthly":
      nextDate.setUTCMonth(nextDate.getUTCMonth() + 1);
      break;
    case "quarterly":
      nextDate.setUTCMonth(nextDate.getUTCMonth() + 3);
      break;
    case "yearly":
      nextDate.setUTCFullYear(nextDate.getUTCFullYear() + 1);
      break;
  }

  return nextDate;
}
