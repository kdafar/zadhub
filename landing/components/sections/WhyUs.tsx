export default function WhyUs({ data }: { data: { items: { title: string; body?: string; icon?: string }[] } }) {
  const items = data?.items || []
  if (!items.length) return null
  return (
    <section className="bg-slate-50">
      <div className="max-w-7xl mx-auto px-6 py-16">
        <div className="grid md:grid-cols-3 gap-8">
          {items.map((f, i) => (
            <div key={i} className="p-6 bg-white rounded-2xl shadow-sm">
              <div className="text-2xl mb-3">{f.icon || '✅'}</div>
              <h3 className="text-lg font-bold mb-2">{f.title}</h3>
              {f.body && <p className="text-slate-600">{f.body}</p>}
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
