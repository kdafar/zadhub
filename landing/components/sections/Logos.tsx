"use client";

import Image from "next/image";
import { motion } from "framer-motion";
import { resolveAssetUrl } from "@/lib/url";

// --- Type Definitions ---
type LogoItem = {
  logo: string;
  alt?: string;
};

type LogosProps = {
  data: {
    eyebrow?: string;
    heading?: string;
    items: LogoItem[];
  };
};

// --- Main Component ---
export default function Logos({ data }: LogosProps) {
  const items = data?.items || [];
  if (!items.length) return null;

  return (
    <section id="logos" className="bg-secondary text-foreground section-padding">
      <div className="container">
        {/* --- Section Heading --- */}
        <div className="section-heading">
          {data.eyebrow && <span className="eyebrow">{data.eyebrow}</span>}
          {data.heading && <h2 className="heading">{data.heading}</h2>}
        </div>

        {/* --- Logos Grid --- */}
        <div className="mt-12 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-x-6 gap-y-10 items-center">
          {items.map((item, i) => {
            const logoUrl = resolveAssetUrl(item.logo);
            if (!logoUrl) return null;

            return (
              <motion.div
                key={i}
                initial={{ opacity: 0, y: 10 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true, amount: 0.5 }}
                transition={{ duration: 0.4, delay: i * 0.05 }}
                className="flex justify-center"
              >
                <div className="relative h-12 w-40 transition-all duration-300 ease-in-out grayscale hover:grayscale-0 hover:scale-105">
                  <Image
                    src={logoUrl}
                    alt={item.alt || "Client logo"}
                    fill
                    sizes="(max-width: 640px) 50vw, (max-width: 1024px) 33vw, 16.6vw"
                    className="object-contain"
                  />
                </div>
              </motion.div>
            );
          })}
        </div>
      </div>
    </section>
  );
}
