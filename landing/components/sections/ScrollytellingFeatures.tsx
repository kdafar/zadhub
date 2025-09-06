"use client";

import Image from "next/image";
import { motion } from "framer-motion";
import { resolveAssetUrl } from "@/lib/url";

// --- Type Definitions ---
type Feature = {
  title: string;
  body?: string;
  icon?: string;
};

type ScrollytellingProps = {
  data: {
    eyebrow?: string;
    heading?: string;
    subheading?: string;
    image?: string;
    features: Feature[];
  };
};

// --- Main Component ---
export default function ScrollytellingFeatures({ data }: ScrollytellingProps) {
  const { eyebrow, heading, subheading, image, features = [] } = data;
  if (!features.length) return null;

  const imageUrl = resolveAssetUrl(image);

  return (
    <section id="scrollytelling-features" className="bg-background text-foreground section-padding overflow-hidden">
      <div className="container">
        {/* --- Section Heading --- */}
        <div className="section-heading">
          {eyebrow && <span className="eyebrow">{eyebrow}</span>}
          {heading && <h2 className="heading">{heading}</h2>}
          {subheading && <p className="subheading">{subheading}</p>}
        </div>

        {/* --- Scrollytelling Grid --- */}
        <div className="mt-16 grid grid-cols-1 items-start gap-12 lg:grid-cols-2 lg:gap-16">
          {/* Left Column: Sticky Image */}
          <div className="sticky top-24 hidden lg:block">
            <motion.div
              initial={{ opacity: 0, scale: 0.9 }}
              whileInView={{ opacity: 1, scale: 1 }}
              viewport={{ once: true, amount: 0.3 }}
              transition={{ duration: 0.7, ease: "easeOut" }}
            >
              {imageUrl && (
                <Image
                  src={imageUrl}
                  alt={heading || "Feature mockup"}
                  width={1000}
                  height={1000}
                  className="w-full h-auto object-contain"
                />
              )}
            </motion.div>
          </div>

          {/* Right Column: Scrolling Features */}
          <div className="space-y-8">
            {features.map((feature, i) => (
              <motion.div
                key={i}
                initial={{ opacity: 0, x: 50 }}
                whileInView={{ opacity: 1, x: 0 }}
                viewport={{ once: true, amount: 0.5 }}
                transition={{ duration: 0.6, ease: "easeOut", delay: 0.1 }}
                className="card card-hover flex min-h-[200px] gap-6 p-6"
              >
                {feature.icon && (
                  <div className="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-primary/10 text-3xl text-primary">
                    {feature.icon}
                  </div>
                )}
                <div>
                  <h3 className="text-xl font-bold">{feature.title}</h3>
                  {feature.body && (
                    <p className="mt-2 text-muted-foreground">{feature.body}</p>
                  )}
                </div>
              </motion.div>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}
