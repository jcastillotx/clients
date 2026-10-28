const { writeFileSync } = require("node:fs");

const keys = [
  "NEXT_PUBLIC_SUPABASE_URL",
  "NEXT_PUBLIC_SUPABASE_ANON_KEY",
  "NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY",
  "NEXT_PUBLIC_APP_URL",
  "NEXT_PUBLIC_SITE_URL",
  "NEXT_PUBLIC_APP_NAME",
  "NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY",
  "NEXT_PUBLIC_TURNSTILE_SITE_KEY",
];

// Public build settings only. Never serialize process.env wholesale.
writeFileSync(".release-public.json", JSON.stringify(
  Object.fromEntries(keys.map((key) => [key, process.env[key] || ""])),
));
