export default function FAQ({ data }: { data: { items: { q: string; a: string }[] } }) {
  const items = data?.items || []
  if (!items.length) return null

  return (
    <section id="faq" className="bg-white anchor-offset">
      <div className="container-pro py-16">
        <h2 className="text-2xl font-extrabold mb-6">FAQ</h2>

        <div className="space-y-3">
          {items.map((it, i) => (
            <details key={i} className="group border rounded-xl p-4 open:shadow-sm">
              <summary className="font-semibold cursor-pointer list-none flex justify-between items-center">
                <span>{it.q}</span>
                <span className="ml-3 transition group-open:rotate-45">➕</span>
              </summary>
              <p className="text-slate-600 mt-3 text-sm">{it.a}</p>
            </details>
          ))}
        </div>
      </div>
    </section>
  )
}
