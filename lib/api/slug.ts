export function slugify(value: string): string {
  return value
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/(^-|-$)/g, "");
}

export function uniqueCodeFromName(name: string): string {
  const base = name.replace(/[^a-zA-Z0-9]/g, "").slice(0, 6).toUpperCase() || "PTR";
  const suffix = Math.random().toString(36).slice(2, 6).toUpperCase();
  return `${base}${suffix}`;
}
