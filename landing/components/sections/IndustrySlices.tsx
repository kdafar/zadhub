import Image from 'next/image'
import clsx from 'clsx'
import { resolveAssetUrl } from '@/lib/url'

type Slice = {
  kicker?: string
  headline?: string
  copy?: string
  image?: string
  reverse?: boolean
}

export default function IndustrySlices({ data }: { data: { slices: Slice[] } }) {
  const slices = data?.slices || []
  if (!slices.length) return null

  return (
    <section id="industries" className="bg-white">
      <div className="container-pro py-16 space-y-16">
        {slices.map((s, i) => {
          const img = resolveAssetUrl(s.image)
          return (
            <div
              key={i}
              className={clsx(
                'grid items-center gap-8 md:grid-cols-2',
                s.reverse && 'md:[&>*:first-child]:order-2'
              )}
            >
              {/* Text */}
              <div>
                {s.kicker && (
                  <p className="text-xs uppercase tracking-[.2em] opacity-60">{s.kicker}</p>
                )}
                {s.headline && (
                  <h3 className="text-2xl font-extrabold mt-2 mb-3">{s.headline}</h3>
                )}
                {s.copy && <p className="text-slate-600 text-sm">{s.copy}</p>}
              </div>

              {/* Image */}
              <div>
                {img && (
                  <Image
                    src={img}
                    alt=""
                    width={1000}
                    height={700}
                    className="w-full h-auto rounded-2xl shadow-sm"
                  />
                )}
              </div>
            </div>
          )
        })}
      </div>
    </section>
  )
}
