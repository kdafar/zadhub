export default function Pricing({
  data,
}: {
  data: {
    plans: {
      name: string
      price_text: string
      summary?: string
      bullets?: { text: string }[]
      cta?: { label: string; href: string }
      featured?: boolean
    }[]
    note?: string
  }
}) {
  const plans = data?.plans || []
  if (!plans.length) return null

  return (
    <section id="pricing" className="bg-slate-50 anchor-offset">
      <div className="container-pro py-16">
        <div className="grid gap-6 md:grid-cols-3">
          {plans.map((p, i) => (
            <div
              key={i}
              className={`relative p-6 rounded-2xl bg-white shadow-sm border ${
                p.featured ? 'border-emerald-300 ring-2 ring-emerald-200' : 'border-slate-200'
              }`}
            >
              {p.featured && (
                <div className="absolute -top-3 right-6 text-xs px-2 py-1 rounded-full bg-emerald-600 text-white shadow">
                  Most Popular
                </div>
              )}

              <div className="flex items-baseline justify-between">
                <h3 className="text-xl font-extrabold">{p.name}</h3>
                <div className="text-emerald-700 font-bold">{p.price_text}</div>
              </div>

              {p.summary && <p className="text-slate-600 mt-2 text-sm">{p.summary}</p>}

              <ul className="mt-4 space-y-2">
                {(p.bullets || []).map((b, j) => (
                  <li key={j} className="flex gap-2 text-sm">
                    <span>✔️</span>
                    <span>{b.text}</span>
                  </li>
                ))}
              </ul>

              {p.cta?.label && (
                <a
                  href={p.cta.href || '#lead'}
                  className={`mt-6 inline-block px-5 py-3 rounded-xl font-semibold hover:opacity-90 ${
                    p.featured ? 'bg-emerald-600 text-white' : 'border'
                  }`}
                >
                  {p.cta.label}
                </a>
              )}
            </div>
          ))}
        </div>

        {data.note && <p className="text-xs text-slate-500 mt-6">{data.note}</p>}
      </div>
    </section>
  )
}
