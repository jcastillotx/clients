import { format } from "date-fns";

export function formatDate(date: string | Date | number) {
  if (!date) return "";
  const dateObj = typeof date === "string" || typeof date === "number" ? new Date(date) : date;
  return format(dateObj, "MMM d, yyyy");
}

export function formatDateTime(date: string | Date | number) {
  if (!date) return "";
  const dateObj = typeof date === "string" || typeof date === "number" ? new Date(date) : date;
  return format(dateObj, "MMM d, yyyy h:mm a");
}

export function formatCurrency(amount: number | string, currency: string = "USD") {
  const numericAmount = typeof amount === "string" ? parseFloat(amount) : amount;
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: currency || "USD",
  }).format(numericAmount || 0);
}

export function formatCompactNumber(number: number) {
  return new Intl.NumberFormat("en-US", {
    notation: "compact",
    maximumFractionDigits: 1,
  }).format(number);
}
