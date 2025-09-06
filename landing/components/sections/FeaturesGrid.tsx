"use client";

import { motion } from "framer-motion";

// --- Type Definitions ---
type FeatureItem = {
  title: string;
  body?: string;
  icon?: string;
};

type FeaturesGridProps = {
  data: {
    eyebrow?: string;
    heading?: string;
    subheading?: string;
    features: FeatureItem[];
  };
};

// --- Main Component ---
export default function FeaturesGrid({ data }: FeaturesGridProps) {
  const items = data?.features || [];
  if (!items.length) return null;

  return (
    <section id="features" className="bg-background text-foreground section-padding">
      <div className="container">
        {/* --- Section Heading --- */}
        <div className="section-heading">
          {data.eyebrow && <span className="eyebrow">{data.eyebrow}</span>}
          {data.heading && <h2 className="heading">{data.heading}</h2>}
          {data.subheading && <p className="subheading">{data.subheading}</p>}
        </div>

        {/* --- Features Grid --- */}
        <div className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {items.map((feature, i) => (
            <motion.div
              key={i}
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true, amount: 0.5 }}
              transition={{ duration: 0.5, delay: i * 0.1 }}
            >
              {/* Use the .card utility for consistent styling */}
              <div className="card h-full p-6 text-left">
                {/* Styled Icon Wrapper */}
                {feature.icon && (
                  <div className="mb-4 inline-flex items-center justify-center h-12 w-12 rounded-lg bg-primary/10 text-primary text-2xl">
                    {feature.icon}
                  </div>
                )}
                
                {/* Let globals.css handle the h3 fluid typography */}
                <h3 className="font-bold">{feature.title}</h3>
                
                {/* Let globals.css handle the p fluid typography */}
                {feature.body && <p className="mt-2">{feature.body}</p>}
              </div>
            </motion.div>
          ))}
        </div>
      </div>
    </section>
  );
}
