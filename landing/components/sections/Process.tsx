"use client";

import { motion } from "framer-motion";

// --- Type Definitions ---
type ProcessItem = {
  title: string;
  body?: string;
};

type ProcessProps = {
  data: {
    eyebrow?: string;
    heading?: string;
    subheading?: string;
    items: ProcessItem[];
  };
};

// --- Main Component ---
export default function Process({ data }: ProcessProps) {
  const items = data?.items || [];
  if (!items.length) return null;

  return (
    <section id="process" className="bg-secondary text-foreground section-padding">
      <div className="container">
        {/* --- Section Heading --- */}
        <div className="section-heading">
          {data.eyebrow && <span className="eyebrow">{data.eyebrow}</span>}
          {data.heading && <h2 className="heading">{data.heading}</h2>}
          {data.subheading && <p className="subheading">{data.subheading}</p>}
        </div>

        {/* --- Process Steps Grid --- */}
        <div className="relative mt-12 grid grid-cols-1 gap-8 md:grid-cols-3">
          {/* Dashed line connecting the steps on desktop */}
          <div className="pointer-events-none absolute inset-0 hidden items-center justify-center md:flex">
            <div className="h-px w-full -translate-y-4 border-2 border-dashed border-border"></div>
          </div>

          {items.map((item, i) => (
            <motion.div
              key={i}
              initial={{ opacity: 0, y: 30 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true, amount: 0.5 }}
              transition={{ duration: 0.5, delay: i * 0.15 }}
              className="card card-hover relative flex h-full flex-col items-center p-6 text-center"
            >
              {/* Step Number Circle */}
              <div className="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-primary font-display text-2xl font-bold text-primary-foreground">
                {i + 1}
              </div>

              {/* Step Content */}
              <h3 className="text-xl font-bold">{item.title}</h3>
              {item.body && <p className="mt-2 text-muted-foreground">{item.body}</p>}
            </motion.div>
          ))}
        </div>
      </div>
    </section>
  );
}
