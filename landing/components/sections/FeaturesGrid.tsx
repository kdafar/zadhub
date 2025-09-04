export default function FeaturesGrid({
  data,
}: {
  data: { features: { title: string; body?: string; icon?: string }[] }
}) {
  const items = data?.features || []
  if (!items.length) return null

  return (
    <section id="features" className="bg-slate-50 anchor-offset">
      <div className="container-pro py-16">
        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
          {items.map((f, i) => (
            <div key={i} className="card card-hover p-6">
              <div className="text-2xl mb-3">{f.icon || '⚡'}</div>
              <h3 className="text-lg font-bold mb-1">{f.title}</h3>
              {f.body && <p className="text-slate-600 text-sm">{f.body}</p>}
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
