export function resolveAssetUrl(path?: string): string {
  if (!path) return '';
  // already absolute?
  if (/^https?:\/\//i.test(path)) return path;

  const base = (process.env.NEXT_PUBLIC_BACKEND_URL || '').replace(/\/$/, '');
  // if starts with "/storage/..." just prefix the backend origin
  if (path.startsWith('/')) return `${base}${path}`;

  // otherwise treat as storage-relative e.g. "landing/hero/file.png"
  return `${base}/storage/${path.replace(/^storage\/?/, '')}`;
}
