import { readFileSync } from "node:fs";
import { withSentryConfig } from "@sentry/nextjs";

/** @type {import('next').NextConfig} */
const nextConfig = {
  output: "standalone",
  experimental: { cpus: 2 },
  async headers() {
    const vercelConfig = JSON.parse(
      readFileSync(new URL("./vercel.json", import.meta.url), "utf8"),
    );
    return vercelConfig.headers;
  },
  images: {
    remotePatterns: [
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
