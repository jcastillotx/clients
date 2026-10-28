import { readFileSync } from "node:fs";
import { withSentryConfig } from "@sentry/nextjs";
import { isLocalProviderAcceptance } from "./lib/staging/provider-acceptance.mjs";

/** @type {import('next').NextConfig} */
const nextConfig = {
  output: "standalone",
  experimental: { cpus: 2 },
  async headers() {
    const securityHeaders = JSON.parse(
      readFileSync(
        new URL("./config/security-headers.json", import.meta.url),
        "utf8",
      ),
    );
    if (process.env.STAGING_ISOLATED === "1" || isLocalProviderAcceptance()) {
      for (const route of securityHeaders) {
        for (const header of route.headers) {
          if (header.key.toLowerCase() === "content-security-policy") {
            header.value = header.value
              .replace(/upgrade-insecure-requests;?/g, "")
              .replace(
                "connect-src ",
                "connect-src http://127.0.0.1:55321 ws://127.0.0.1:55321 ",
              )
              .replace(
                "img-src ",
                "img-src http://127.0.0.1:55321 http://127.0.0.1:59000 ",
              );
          }
        }
      }
    }
    return securityHeaders;
  },
  images: {
    remotePatterns: [
      {
        protocol: "https",
        hostname: "www.kre8ivdesigns.com",
        pathname: "/assets/logo-mark.png",
      },
      {
        protocol: "https",
        hostname: "www.kre8ivdesigns.com",
        pathname: "/assets/logo.png",
      },
      {
        protocol: "https",
        hostname: "www.kre8ivdesigns.com",
        pathname: "/wp-content/**",
      },
    ],
  },
  serverExternalPackages: ["@react-pdf/renderer"],
  eslint: {
    ignoreDuringBuilds: false,
  },
};

const sentryEnabled = Boolean(process.env.SENTRY_DSN?.trim());

export default sentryEnabled
  ? withSentryConfig(nextConfig, {
      org: process.env.SENTRY_ORG,
      project: process.env.SENTRY_PROJECT,
      authToken: process.env.SENTRY_AUTH_TOKEN,
      silent: !process.env.CI,
      widenClientFileUpload: true,
      hideSourceMaps: true,
      disableLogger: true,
      tunnelRoute: "/monitoring",
    })
  : nextConfig;
