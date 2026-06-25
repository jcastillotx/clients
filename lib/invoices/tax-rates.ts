/**
 * US state sales tax rates (approximate combined state + local averages).
 * Used to suggest tax when a client's billing location is known.
 */
const US_STATE_TAX_RATES: Record<string, number> = {
  AL: 9.22,
  AK: 1.76,
  AZ: 8.37,
  AR: 9.43,
  CA: 8.82,
  CO: 7.77,
  CT: 6.35,
  DE: 0,
  FL: 7.02,
  GA: 7.33,
  HI: 4.5,
  ID: 6.03,
  IL: 8.81,
  IN: 7,
  IA: 6.94,
  KS: 8.67,
  KY: 6,
  LA: 9.55,
  ME: 5.5,
  MD: 6,
  MA: 6.25,
  MI: 6,
  MN: 7.49,
  MS: 7.07,
  MO: 8.35,
  MT: 0,
  NE: 6.94,
  NV: 8.23,
  NH: 0,
  NJ: 6.6,
  NM: 7.83,
  NY: 8.52,
  NC: 6.98,
  ND: 6.96,
  OH: 7.23,
  OK: 8.98,
  OR: 0,
  PA: 6.34,
  RI: 7,
  SC: 7.44,
  SD: 6.4,
  TN: 9.55,
  TX: 8.2,
  UT: 7.19,
  VT: 6.24,
  VA: 5.75,
  WA: 9.38,
  WV: 6.5,
  WI: 5.43,
  WY: 5.36,
  DC: 6,
};

const COUNTRY_DEFAULT_RATES: Record<string, number> = {
  US: 0,
  CA: 5,
  GB: 20,
  AU: 10,
};

export interface ClientTaxLocation {
  city?: string | null;
  state?: string | null;
  country?: string | null;
}

function normalizeState(state: string | null | undefined): string | null {
  if (!state) return null;
  const trimmed = state.trim().toUpperCase();
  if (trimmed.length === 2) return trimmed;
  return null;
}

function normalizeCountry(country: string | null | undefined): string {
  if (!country) return "US";
  const trimmed = country.trim().toUpperCase();
  if (trimmed === "USA" || trimmed === "UNITED STATES") return "US";
  if (trimmed.length === 2) return trimmed;
  return trimmed.slice(0, 2);
}

/**
 * Derive a suggested tax rate (percentage) from client billing location.
 */
export function getTaxRateForClient(location: ClientTaxLocation): number {
  const country = normalizeCountry(location.country);
  const state = normalizeState(location.state);

  if (country === "US" && state) {
    return US_STATE_TAX_RATES[state] ?? 0;
  }

  return COUNTRY_DEFAULT_RATES[country] ?? 0;
}

export function formatTaxLocationLabel(location: ClientTaxLocation): string {
  const parts = [location.city, location.state, location.country].filter(Boolean);
  return parts.length > 0 ? parts.join(", ") : "No location on file";
}
