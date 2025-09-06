"use client";

import Link from "next/link";
import { motion } from "framer-motion";
import { Button } from "@heroui/react";
import clsx from "clsx";

// --- Type Definitions ---
type Plan = {
  name: string;
  price_text: string;
  summary?: string;
  bullets?: { text: string }[];
  cta?: { label: string; href: string };
  featured?: boolean;
};

type PricingProps = {
  data: {
    eyebrow?: string;
    heading?: string;
    subheading?: string;
    plans: Plan[];
    note?: string;
  };
};

// --- SVG Icon for Bullets ---
const CheckIcon = () => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width="20"
    height="20"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth="3"
    strokeLinecap="round"
    strokeLinejoin="round"
    className="h-5 w-5 flex-shrink-0 text-primary"
  >
    <path d="M20 6 9 17l-5-5" />
  </svg>
);

// --- Main Component ---
export default function Pricing({ data }: PricingProps) {
  const plans = data?.plans || [];
  if (!plans.length) return null;

  return (
    <section id="pricing" className="bg-background text-foreground section-padding">
      <div className="container">
        {/* --- Section Heading --- */}
        <div className="section-heading">
          {data.eyebrow && <span className="eyebrow">{data.eyebrow}</span>}
          {data.heading && <h2 className="heading">{data.heading}</h2>}
          {data.subheading && <p className="subheading">{data.subheading}</p>}
        </div>

        {/* --- Pricing Grid --- */}
        <div className="mt-12 grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3 lg:items-center">
          {plans.map((plan, i) => (
            <motion.div
              key={i}
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true, amount: 0.3 }}
              transition={{ duration: 0.6, delay: i * 0.1 }}
              className={clsx(
                "card relative flex h-full flex-col p-6",
                plan.featured && "border-2 border-primary ring-4 ring-primary/10"
              )}
            >
              {plan.featured && (
                <div className="badge absolute -top-4 right-6 bg-primary text-primary-foreground shadow-lg">
                  Most Popular
                </div>
              )}

              {/* Plan Header */}
              <div className="mb-5">
                <h3 className="text-2xl font-bold">{plan.name}</h3>
                <p className="mt-1 text-4xl font-extrabold">{plan.price_text}</p>
                {plan.summary && (
                  <p className="mt-2 text-muted-foreground">{plan.summary}</p>
                )}
              </div>

              {/* Plan Features */}
              <ul className="space-y-3">
                {(plan.bullets || []).map((bullet, j) => (
                  <li key={j} className="flex items-start gap-3">
                    <CheckIcon />
                    <span>{bullet.text}</span>
                  </li>
                ))}
              </ul>

              {/* Plan CTA */}
              {plan.cta?.label && (
                <div className="mt-auto pt-8">
                  <Button
                    as={Link}
                    href={plan.cta.href || "#lead"}
                    className={clsx(
                      "btn btn-lg w-full",
                      plan.featured ? "btn-primary" : "btn-outline"
                    )}
                  >
                    {plan.cta.label}
                  </Button>
                </div>
              )}
            </motion.div>
          ))}
        </div>

        {/* Optional Note */}
        {data.note && (
          <p className="mt-8 text-center text-sm text-muted-foreground">{data.note}</p>
        )}
      </div>
    </section>
  );
}
