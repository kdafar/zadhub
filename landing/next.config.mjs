/** @type {import('next').NextConfig} */
const nextConfig = {
  images: {
    remotePatterns: [
      // add your prod domains as needed:
      { protocol: 'https', hostname: 'zad-hub.com' },
       { protocol: 'http',  hostname: 'zad-hub.com' },
      // { protocol: 'https', hostname: 'cdn.example.com' },
    ],
  },
  experimental: { typedRoutes: true },
}

export default nextConfig
