import Image from 'next/image'
import { resolveAssetUrl } from '@/lib/url'

type Item = { quote: string; author: string; role?: string; avatar?: string; rating?: number }

export default function Testimonials({ data }: { data: { items: Item[] } }) {
  const items = data?.items || []
  if (!items.length) return null

  return (
    <section id="testimonials" className="bg-white anchor-offset">
      <div className="max-w-7xl mx-auto px-6 py-16">
        <h2 className="text-2xl font-extrabold mb-8">What our customers say</h2>
        <div className="grid md:grid-cols-3 gap-6">
          {items.map((t, i) => (
            <figure key={i} className="p-6 rounded-2xl border bg-white shadow-sm">
              <div className="flex items-center gap-3 mb-4">
                {t.avatar && (
                  <Image
                    src={resolveAssetUrl(t.avatar)}
                    alt={t.author}
                    width={48}
                    height={48}
                    className="rounded-full h-12 w-12 object-cover"
                  />
                )}
                <div>
                  <figcaption className="font-semibold">{t.author}</figcaption>
                  {t.role && <div className="text-xs text-slate-500">{t.role}</div>}
                </div>
              </div>
              {typeof t.rating === 'number' && t.rating > 0 && (
                <div className="text-amber-500 mb-3" aria-label={`${t.rating} stars`}>
                  {'★★★★★'.slice(0, Math.min(5, t.rating))}{'☆☆☆☆☆'.slice(0, 5 - Math.min(5, t.rating))}
                </div>
              )}
              <blockquote className="text-slate-700">“{t.quote}”</blockquote>
            </figure>
          ))}
        </div>
      </div>
    </section>
  )
}
