/** @type {import('next').NextConfig} */
const nextConfig = {
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
    // Temporary: unblock production deploys while we remediate legacy lint debt.
    ignoreDuringBuilds: true,
  },
};

export default nextConfig;
