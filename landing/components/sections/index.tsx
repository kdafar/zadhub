import Hero from './Hero'
import FeaturesGrid from './FeaturesGrid'
import Logos from './Logos'
import WhyUs from './WhyUs'
import ScrollytellingFeatures from './ScrollytellingFeatures'
import Pricing from './Pricing'
import FAQ from './FAQ'
import CTA from './CTA'
import Testimonials from './Testimonials'
import Process from './Process' // New component import

// The REGISTRY maps the 'type' from your API to the actual React component.
const REGISTRY: Record<string, any> = {
  hero: Hero,
  logos: Logos,
  why_us: WhyUs,
  process: Process, // New 'process' section
  scrollytelling_features: ScrollytellingFeatures, // New 'scrollytelling' section
  features_grid: FeaturesGrid,
  testimonials: Testimonials,
  pricing: Pricing,
  faq: FAQ,
  cta: CTA,
}

export default function RenderSections({ sections }: { sections: any[] }) {
  return (
    <>
      {(sections || []).map((block, idx) => {
        // Find the component that matches the block's type.
        const Cmp = REGISTRY[block?.type]
        // If no matching component is found, render nothing.
        if (!Cmp) return null
        // Render the component, passing its data as props.
        return <Cmp key={idx} data={block.data} />
      })}
    </>
  )
}
