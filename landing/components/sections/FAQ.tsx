"use client";

import { motion } from "framer-motion";
import { Accordion, AccordionItem } from "@heroui/react";
import { useAutoAnimate } from "@formkit/auto-animate/react";

// --- Type Definitions ---
type FAQItem = {
  q: string;
  a: string;
};

type FAQProps = {
  data: {
    eyebrow?: string;
    heading?: string;
    subheading?: string;
    items: FAQItem[];
  };
};

// --- SVG Icon for Accordion ---
const ChevronDownIcon = ({ isOpen }: { isOpen: boolean }) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width="24"
    height="24"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="2.5"
    strokeLinecap="round"
    strokeLinejoin="round"
    className={`text-primary transition-transform duration-300 ease-in-out ${
      isOpen ? "rotate-180" : ""
    }`}
  >
    <path d="m6 9 6 6 6-6" />
  </svg>
);

// --- Main Component ---
export default function FAQ({ data }: FAQProps) {
  const items = data?.items || [];
  const [parent] = useAutoAnimate<HTMLDivElement>();
  if (!items.length) return null;

  return (
    <section id="faq" className="bg-secondary text-foreground section-padding">
      <div className="container">
        {/* --- Section Heading --- */}
        <div className="section-heading">
          {data.eyebrow && <span className="eyebrow">{data.eyebrow}</span>}
          {data.heading && <h2 className="heading">{data.heading}</h2>}
          {data.subheading && <p className="subheading">{data.subheading}</p>}
        </div>

        {/* --- Accordion Container --- */}
        <motion.div
          ref={parent}
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, amount: 0.2 }}
          transition={{ duration: 0.6, ease: "easeOut" }}
          className="mt-12 max-w-xl mx-auto md:max-w-3xl lg:max-w-4xl"
        >
          <div className="space-y-4">
            <Accordion
              selectionMode="multiple"
              itemClasses={{
                base: "card card-hover",
                trigger: "px-6 py-5",
                content: "px-6 pb-5",
              }}
            >
              {items.map((item, i) => (
                <AccordionItem
                  key={i}
                  aria-label={item.q}
                  title={
                    <h3 className="text-lg font-semibold text-left">
                      {item.q}
                    </h3>
                  }
                  indicator={({ isOpen }) => <ChevronDownIcon isOpen={!!isOpen} />}
                >
                  <p className="text-left text-foreground/80">{item.a}</p>
                </AccordionItem>
              ))}
            </Accordion>
          </div>
        </motion.div>
      </div>
    </section>
  );
}

