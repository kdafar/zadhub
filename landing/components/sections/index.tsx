import Hero from './Hero'
import FeaturesGrid from './FeaturesGrid'
import Logos from './Logos'
import WhyUs from './WhyUs'
import IndustrySlices from './IndustrySlices'
import Pricing from './Pricing'
import FAQ from './FAQ'
import CTA from './CTA'
import Testimonials from './Testimonials'

const REGISTRY: Record<string, any> = {
  hero: Hero,
  features_grid: FeaturesGrid,
  logos: Logos,
  why_us: WhyUs,
  industry_slices: IndustrySlices,
  pricing: Pricing,
  faq: FAQ,
  cta: CTA,
  testimonials: Testimonials,
}

export default function RenderSections({ sections }: { sections: any[] }) {
  return (
    <>
      {(sections || []).map((block, idx) => {
        const Cmp = REGISTRY[block?.type]
        if (!Cmp) return null
        return <Cmp key={idx} data={block.data} />
      })}
    </>
  )
}
