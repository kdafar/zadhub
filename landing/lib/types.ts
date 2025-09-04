export type Block =
  | { type: 'hero'; data: { eyebrow?: string; heading: string; subheading?: string; primary?: {label:string;href:string}; secondary?: {label:string;href:string}; image?: string; dark?: boolean } }
  | { type: 'features_grid'; data: { features: { title: string; body?: string; icon?: string }[] } }
  | { type: 'logos'; data: { items: { logo: string; alt?: string }[] } }
  | { type: 'why_us'; data: { items: { title: string; body?: string; icon?: string }[] } }
  | { type: 'industry_slices'; data: { slices: { kicker?: string; headline?: string; copy?: string; image?: string; reverse?: boolean }[] } }
  | { type: 'pricing'; data: { plans: { name: string; price_text: string; summary?: string; bullets?: {text:string}[]; cta?: {label:string;href:string}; featured?: boolean }[]; note?: string } }
  | { type: 'faq'; data: { items: { q: string; a: string }[] } }
  | { type: 'cta'; data: { heading?: string; subheading?: string; cta?: {label:string;href:string} } }

export type PageResponse = {
  slug: string
  locale: 'en'|'ar'
  title: string
  meta: { title?: string|null; description?: string|null }
  sections: Block[]
  is_published: boolean
  published_at?: string|null
  updated_at: string
  version: number
}
