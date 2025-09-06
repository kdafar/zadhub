/** @type {import('next').NextConfig} */
const nextConfig = {
  // ✅ typedRoutes moved out of experimental
  typedRoutes: true,

  // ✅ allow your dev domains to hit /_next/* (HMR, assets) when not on localhost
  allowedDevOrigins: ['zad-hub.com', '*.zad-hub.com', 'localhost', '127.0.0.1'],

  images: {
    formats: ['image/avif', 'image/webp'],
    remotePatterns: [
      { protocol: 'https', hostname: 'zad-hub.com', pathname: '/storage/**' },
      { protocol: 'http',  hostname: 'zad-hub.com', pathname: '/storage/**' },
    ],
    // (Optional while you sort hosting): unoptimized: true,
  },

  async redirects() {
    return [{ source: '/', destination: '/en', permanent: false }];
  },
};

export default nextConfig;
