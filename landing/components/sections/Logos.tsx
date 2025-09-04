import Image from 'next/image'
import { resolveAssetUrl } from '@/lib/url'

export default function Logos({ data }: { data: { items: { logo: string; alt?: string }[] } }) {
  const items = data?.items || []
  if (!items.length) return null

  return (
    <section id="logos" className="bg-white">
      <div className="container-pro py-10">
        <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6 items-center">
          {items.map((it, i) => (
            <div key={i} className="flex justify-center opacity-80 hover:opacity-100 transition">
              <Image
                src={resolveAssetUrl(it.logo)}
                alt={it.alt || ''}
                width={160}
                height={80}
                className="h-10 w-auto object-contain grayscale hover:grayscale-0"
              />
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
