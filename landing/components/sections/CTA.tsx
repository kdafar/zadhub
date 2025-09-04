export default function CTA({ data }: { data: { heading?: string; subheading?: string; cta?: {label:string;href:string} } }) {
  return (
    <section className="bg-emerald-600 text-white">
      <div className="max-w-7xl mx-auto px-6 py-16 text-center">
        {data?.heading && <h2 className="text-3xl font-extrabold">{data.heading}</h2>}
        {data?.subheading && <p className="opacity-90 mt-2">{data.subheading}</p>}
        {data?.cta?.label && (
          <a href={data.cta.href || '#lead'} className="inline-block mt-6 px-6 py-3 rounded-xl bg-white text-emerald-700 font-semibold hover:opacity-90">
            {data.cta.label}
          </a>
        )}
      </div>
    </section>
  )
}
