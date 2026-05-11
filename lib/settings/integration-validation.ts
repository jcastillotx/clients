import {
  integrationCategoryEnum,
  integrationProviderEnum,
  PROVIDER_CONFIGS,
  type IntegrationCategory,
  type IntegrationProvider,
} from "@/lib/db/schema/encrypted-settings";

export function isIntegrationProvider(value: string): value is IntegrationProvider {
  return (integrationProviderEnum as readonly string[]).includes(value);
}

export function isIntegrationCategory(value: string): value is IntegrationCategory {
  return (integrationCategoryEnum as readonly string[]).includes(value);
}

export function getIntegrationProviderConfig(provider: IntegrationProvider) {
  return PROVIDER_CONFIGS.find((config) => config.provider === provider) ?? null;
}

export function validateIntegrationProviderCategory(
  provider: string,
  category?: string,
):
  | { success: true; provider: IntegrationProvider; category: IntegrationCategory }
  | { success: false; error: string } {
  if (!isIntegrationProvider(provider)) {
    return { success: false, error: "Unsupported integration provider." };
  }

  const providerConfig = getIntegrationProviderConfig(provider);
  if (!providerConfig) {
    return { success: false, error: "Integration provider is not configured." };
  }

  if (!category) {
    return { success: true, provider, category: providerConfig.category };
  }

  if (!isIntegrationCategory(category)) {
    return { success: false, error: "Unsupported integration category." };
  }

  if (providerConfig.category !== category) {
    return {
      success: false,
      error: `${providerConfig.displayName} must be saved under the ${providerConfig.category} category.`,
    };
  }

  return { success: true, provider, category };
}
