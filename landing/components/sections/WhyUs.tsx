"use client";

import { motion } from "framer-motion";

// --- Type Definitions ---
type Item = {
  title: string;
  body?: string;
  icon?: string;
};

type WhyUsProps = {
  data: {
    eyebrow?: string;
    heading?: string;
    subheading?: string;
    items: Item[];
  };
};

// --- Main Component ---
export default function WhyUs({ data }: WhyUsProps) {
  const items = data?.items || [];
  if (!items.length) return null;

  return (
    <section id="why-us" className="bg-background text-foreground section-padding">
      <div className="container">
        {/* --- Section Heading --- */}
        <div className="section-heading">
          {data.eyebrow && <span className="eyebrow">{data.eyebrow}</span>}
          {data.heading && <h2 className="heading">{data.heading}</h2>}
          {data.subheading && <p className="subheading">{data.subheading}</p>}
        </div>

        {/* --- Items Grid --- */}
        <div className="mt-12 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
          {items.map((item, i) => (
            <motion.div
              key={i}
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true, amount: 0.5 }}
              transition={{ duration: 0.5, delay: i * 0.1 }}
            >
              <div className="card h-full p-6 text-center">
                {/* Styled Icon Wrapper */}
                {item.icon && (
                  <div className="mb-4 inline-flex items-center justify-center h-14 w-14 rounded-full bg-primary/10 text-primary text-3xl">
                    {item.icon}
                  </div>
                )}
                
                {/* Let globals.css handle the h3 fluid typography */}
                <h3 className="font-bold">{item.title}</h3>
                
                {/* Let globals.css handle the p fluid typography */}
                {item.body && <p className="mt-2">{item.body}</p>}
              </div>
            </motion.div>
          ))}
        </div>
      </div>
    </section>
  );
}
