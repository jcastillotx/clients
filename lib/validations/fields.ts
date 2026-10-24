import { z } from "zod";

export const cleanTextSchema = z.string().trim().regex(/^[^\u0000-\u0008\u000b\u000c\u000e-\u001f\u007f]*$/, "Remove invalid control characters.");

export const emailSchema = cleanTextSchema.max(254).email("Enter a valid email address.");
export const phoneSchema = cleanTextSchema.max(80).refine((value) => {
  if (!value) return true;
  const parts = value.split(/\s*(?:ext\.?|x|#)\s*/i);
  const digits = parts[0].replace(/\D/g, "");
  return parts.length <= 2 && /^\+?[\d\s().-]+$/.test(parts[0]) && digits.length >= 7 && digits.length <= 15 && (parts[1] === undefined || /^\d{1,6}$/.test(parts[1]));
}, "Enter a valid phone number with 7–15 digits, or leave it blank.");

export const websiteSchema = cleanTextSchema.max(2048).refine((value) => {
  if (!value) return true;
  try {
    const url = new URL(/^[a-z][a-z\d+.-]*:/i.test(value) ? value : `https://${value}`);
    return ["http:", "https:"].includes(url.protocol) && !url.username && !url.password && !/\s/.test(value) && url.hostname.includes(".") && url.hostname.split(".").every(part => /^[a-z\d](?:[a-z\d-]{0,61}[a-z\d])?$/i.test(part));
  } catch { return false; }
}, "Enter a valid website address, such as https://company.com.");

export const calendarDateSchema = cleanTextSchema.refine((value) => {
  if (!/^\d{4}-\d{2}-\d{2}(?:T\d{2}:\d{2}:\d{2}(?:\.\d{1,3})?(?:Z|[+-]\d{2}:\d{2}))?$/.test(value)) return false;
  const date = value.slice(0, 10);
  return Number.isFinite(Date.parse(value)) && Number.isFinite(Date.parse(date)) && new Date(date).toISOString().slice(0, 10) === date;
}, "Select a real calendar date.");

export function checkDateOrder(start: string | null | undefined, end: string | null | undefined, path: string, context: z.RefinementCtx) {
  if (start && end && Date.parse(end) < Date.parse(start)) context.addIssue({code: z.ZodIssueCode.custom, path: [path], message: "The end or launch date cannot be before the start date."});
}

export function checkUpcomingDates(data: {requestedStartDate?: string | null; requestedLaunchDate?: string | null; dueDate?: string | null}, context: z.RefinementCtx) {
  const today = new Intl.DateTimeFormat("en-CA", {timeZone: "America/Chicago", year: "numeric", month: "2-digit", day: "2-digit"}).format(new Date());
  for (const key of ["requestedStartDate", "requestedLaunchDate", "dueDate"] as const) {
    if (data[key] && data[key]!.slice(0, 10) < today) context.addIssue({code: z.ZodIssueCode.custom, path: [key], message: "Requested project dates cannot be in the past."});
  }
  checkDateOrder(data.requestedStartDate, data.requestedLaunchDate || data.dueDate, "requestedLaunchDate", context);
}

const countryKey = (value: string) => value.normalize("NFKD").replace(/\p{M}/gu, "").toLowerCase().replace(/[^\p{L}\p{N}]/gu, "");
const countryNames = new Set<string>(["usa", "us", "unitedstatesofamerica", "uk", "greatbritain", "uae"]);
const countryLabels = new Set<string>();
for (const language of ["en", "es", "fr", "de", "pt"]) {
  const names = new Intl.DisplayNames([language], {type: "region"});
  for (let first = 65; first <= 90; first++) for (let second = 65; second <= 90; second++) {
    const code = String.fromCharCode(first, second);
    if (["XA", "XB", "ZZ", "EU", "EZ", "UN"].includes(code)) continue;
    const label = names.of(code);
    if (label && label !== code) { countryNames.add(countryKey(label)); countryNames.add(code.toLowerCase()); if (language === "en") countryLabels.add(label); }
  }
}
export const countryOptions = [...countryLabels].sort();
export const countrySchema = cleanTextSchema.max(100).refine(value => !value || countryNames.has(countryKey(value)), "Select a recognized country or enter its two-letter country code.");
export function checkPostalCode(data: {country?: string | null; zipCode?: string | null}, context: z.RefinementCtx) {
  if (data.zipCode && ["us", "usa", "unitedstates", "unitedstatesofamerica"].includes(countryKey(data.country || "")) && !/^\d{5}(?:-\d{4})?$/.test(data.zipCode)) context.addIssue({code:z.ZodIssueCode.custom,path:["zipCode"],message:"Enter a 5-digit US ZIP code or ZIP+4."});
}
