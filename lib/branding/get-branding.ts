import { and, desc, eq, inArray } from "drizzle-orm";
import { db } from "@/lib/db";
import { whiteLabelConfigs } from "@/lib/db/schema/additional-features";
import { clients } from "@/lib/db/schema/clients";

export interface BrandingSettings {
  sidebarBgColor: string;
  sidebarBgColorDark: string;
  sidebarTextColor: string;
  sidebarTextColorDark: string;
  sidebarWidth: "narrow" | "standard" | "wide";
  brandText: string;
  loginImageUrl: string | null;
  fontSize: "sm" | "md" | "lg";
  primaryColor: string;
  primaryColorDark: string;
  paddingSize: "compact" | "standard" | "spacious";
}

export interface PortalBranding {
  logoUrl: string | null;
  primaryColor: string;
  secondaryColor: string;
  settings: BrandingSettings;
}

const DEFAULT_SETTINGS: BrandingSettings = {
  sidebarBgColor: "#FFFFFF",
  sidebarBgColorDark: "#1E1B4B",
  sidebarTextColor: "#1E1B4B",
  sidebarTextColorDark: "#E8E5FF",
  sidebarWidth: "standard",
  brandText: "",
  loginImageUrl: null,
  fontSize: "md",
  primaryColor: "#7C3AED",
  primaryColorDark: "#A78BFA",
  paddingSize: "standard",
};

function parseSettings(customCss: string | null): BrandingSettings {
  if (!customCss) return { ...DEFAULT_SETTINGS };
  try {
    const parsed = JSON.parse(customCss) as Partial<BrandingSettings>;
    return {
      sidebarBgColor: parsed.sidebarBgColor ?? DEFAULT_SETTINGS.sidebarBgColor,
      sidebarBgColorDark: parsed.sidebarBgColorDark ?? DEFAULT_SETTINGS.sidebarBgColorDark,
      sidebarTextColor: parsed.sidebarTextColor ?? DEFAULT_SETTINGS.sidebarTextColor,
      sidebarTextColorDark: parsed.sidebarTextColorDark ?? DEFAULT_SETTINGS.sidebarTextColorDark,
      sidebarWidth: parsed.sidebarWidth ?? DEFAULT_SETTINGS.sidebarWidth,
      brandText: parsed.brandText ?? DEFAULT_SETTINGS.brandText,
      loginImageUrl: parsed.loginImageUrl ?? DEFAULT_SETTINGS.loginImageUrl,
      fontSize: parsed.fontSize ?? DEFAULT_SETTINGS.fontSize,
      primaryColor: parsed.primaryColor ?? DEFAULT_SETTINGS.primaryColor,
      primaryColorDark: parsed.primaryColorDark ?? DEFAULT_SETTINGS.primaryColorDark,
      paddingSize: parsed.paddingSize ?? DEFAULT_SETTINGS.paddingSize,
    };
  } catch {
    return { ...DEFAULT_SETTINGS };
  }
}

function buildBranding(row: {
  logoUrl: string | null;
  primaryColor: string | null;
  secondaryColor: string | null;
  customCss: string | null;
}): PortalBranding {
  return {
    logoUrl: row.logoUrl,
    primaryColor: row.primaryColor ?? "#7C3AED",
    secondaryColor: row.secondaryColor ?? "#ffffff",
    settings: parseSettings(row.customCss),
  };
}

const FULL_SELECT = {
  logoUrl: whiteLabelConfigs.logoUrl,
  primaryColor: whiteLabelConfigs.primaryColor,
  secondaryColor: whiteLabelConfigs.secondaryColor,
  customCss: whiteLabelConfigs.customCss,
} as const;

const DEFAULT_BRANDING: PortalBranding = {
  logoUrl: null,
  primaryColor: "#7C3AED",
  secondaryColor: "#ffffff",
  settings: { ...DEFAULT_SETTINGS },
};

export async function getPortalBranding(hostname?: string): Promise<PortalBranding> {
  try {
    if (hostname) {
      const byDomain = await db
        .select(FULL_SELECT)
        .from(whiteLabelConfigs)
        .where(and(eq(whiteLabelConfigs.domain, hostname), eq(whiteLabelConfigs.isActive, true)))
        .limit(1);

      if (byDomain[0]) {
        return buildBranding(byDomain[0]);
      }
    }

    const parentClientIds = (process.env.PARENT_CLIENT_IDS ?? "")
      .split(",")
      .map((value) => value.trim())
      .filter(Boolean);

    if (parentClientIds.length > 0) {
      const byParentClient = await db
        .select(FULL_SELECT)
        .from(whiteLabelConfigs)
        .where(and(eq(whiteLabelConfigs.isActive, true), inArray(whiteLabelConfigs.clientId, parentClientIds)))
        .orderBy(desc(whiteLabelConfigs.updatedAt))
        .limit(1);

      if (byParentClient[0]) {
        return buildBranding(byParentClient[0]);
      }
    }

    const parentCompanyNames = (process.env.PARENT_COMPANY_NAMES ?? "Kre8ivTech,Kre8iv Designs")
      .split(",")
      .map((value) => value.trim())
      .filter(Boolean);

    if (parentCompanyNames.length > 0) {
      const parentClients = await db
        .select({ id: clients.id })
        .from(clients)
        .where(inArray(clients.companyName, parentCompanyNames))
        .limit(5);
      const parentClientIdsByName = parentClients.map((row) => row.id);

      if (parentClientIdsByName.length > 0) {
        const byParentName = await db
          .select(FULL_SELECT)
          .from(whiteLabelConfigs)
          .where(and(eq(whiteLabelConfigs.isActive, true), inArray(whiteLabelConfigs.clientId, parentClientIdsByName)))
          .orderBy(desc(whiteLabelConfigs.updatedAt))
          .limit(1);

        if (byParentName[0]) {
          return buildBranding(byParentName[0]);
        }
      }
    }

    const fallback = await db
      .select(FULL_SELECT)
      .from(whiteLabelConfigs)
      .where(eq(whiteLabelConfigs.isActive, true))
      .orderBy(desc(whiteLabelConfigs.updatedAt))
      .limit(1);

    if (fallback[0]) {
      return buildBranding(fallback[0]);
    }

    return { ...DEFAULT_BRANDING, settings: { ...DEFAULT_SETTINGS } };
  } catch {
    return { ...DEFAULT_BRANDING, settings: { ...DEFAULT_SETTINGS } };
  }
}
