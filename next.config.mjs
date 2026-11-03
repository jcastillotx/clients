import { readFileSync } from "node:fs";
import { withSentryConfig } from "@sentry/nextjs";

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
    // Middleware supplies a fresh nonce policy, including on redirects/errors.
    for (const route of securityHeaders) {
      route.headers = route.headers.filter(
        (header) => header.key.toLowerCase() !== "content-security-policy",
      );
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
