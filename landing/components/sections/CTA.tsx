"use client";

import Link from "next/link";
import { motion } from "framer-motion";
import { Button } from "@heroui/react";

// --- Type Definitions ---
type CTAProps = {
  data: {
    heading?: string;
    subheading?: string;
    cta?: {
      label: string;
      href: string;
    };
  };
};

// --- Main Component ---
export default function CTA({ data }: CTAProps) {
  if (!data?.heading && !data?.cta?.label) {
    return null;
  }

  return (
    <section className="bg-primary text-primary-foreground">
      <div className="container section-padding">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, amount: 0.5 }}
          transition={{ duration: 0.6, ease: "easeOut" }}
          className="section-heading"
        >
          {data.heading && (
            // Use the h2 tag and let globals.css handle fluid typography
            <h2 className="heading !text-primary-foreground">{data.heading}</h2>
          )}

          {data.subheading && (
            // Use the p tag and let globals.css handle fluid typography
            <p className="subheading !text-primary-foreground/80">{data.subheading}</p>
          )}

          {data.cta?.label && (
            <div className="mt-8">
              <Button
                as={Link}
                href={data.cta.href || "#lead"}
                // Apply custom button classes for an inverted, high-contrast look
                className="btn btn-lg bg-primary-foreground text-primary shadow-lg hover:-translate-y-1 hover:shadow-xl transition-all"
              >
                {data.cta.label}
              </Button>
            </div>
          )}
        </motion.div>
      </div>
    </section>
  );
}
