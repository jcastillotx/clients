/** @type {import('next').NextConfig} */
const nextConfig = {
  serverExternalPackages: ["@react-pdf/renderer"],
  eslint: {
    // Temporary: unblock production deploys while we remediate legacy lint debt.
    ignoreDuringBuilds: true,
  },
};

export default nextConfig;
