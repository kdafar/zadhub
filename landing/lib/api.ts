// in lib/api.ts

import { PageResponse } from './types'

const API = process.env.BACKEND_URL || process.env.NEXT_PUBLIC_BACKEND_URL

export async function fetchPage(slug: string, locale: 'en'|'ar'): Promise<PageResponse | null> {
  const res = await fetch(`${API}/api/pages/${slug}?locale=${locale}`, { next: { revalidate: 300 } })

  // ✅ If the page specifically is not found, return null
  if (res.status === 404) {
    return null;
  }

  // ✅ For any other error (like a server 500), still throw an error
  if (!res.ok) {
    throw new Error(`Failed to load page: ${res.status}`)
  }

  return res.json()
}