"use client";

import Image from "next/image";
import Link from "next/link";
import { motion } from "framer-motion";
import { Button, Chip, Card, CardBody } from "@heroui/react";
import clsx from "clsx";
import { resolveAssetUrl } from "@/lib/url";
import Balancer from "react-wrap-balancer";

// --- Type Definitions ---
type Btn = { label?: string; href?: string; target?: string; rel?: string };
type Feature = { title: string; desc?: string };
type Data = {
  eyebrow?: string;
  heading?: string;
  subheading?: string;
  primary?: Btn;
  secondary?: Btn;
  image?: string;
  features?: Feature[];
  align?: "left" | "center";
};

// --- Main Component ---
export default function Hero({ data }: { data: Data }) {
  const img = resolveAssetUrl?.(data?.image ?? "") || undefined;
  const align = data?.align ?? "left";

  return (
    <section className="bg-background text-foreground relative overflow-hidden">
      {/* Enhanced theme-aware background gradient utility */}
      <div
        aria-hidden
        className="pointer-events-none absolute inset-0 -z-10 bg-hero-brand dark:bg-hero-dark"
      />

      <div className="container section-padding">
        <div className="grid lg:grid-cols-2 gap-12 items-center">
          {/* --- Copy Section --- */}
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6, ease: "easeOut" }}
            className={clsx(
              "space-y-6",
              align === "center" ? "text-center" : "text-left"
            )}
          >
            {data?.eyebrow ? (
              <Chip
                color="primary"
                variant="flat"
                className="uppercase tracking-widest font-semibold"
              >
                {data.eyebrow}
              </Chip>
            ) : null}

            {data?.heading ? (
              <h1 className="font-display font-extrabold gradient-text leading-tight">
                <Balancer>{data.heading}</Balancer>
              </h1>
            ) : null}

            {data?.subheading ? (
              <p
                className={clsx(
                  "max-w-2xl",
                  align === "center" ? "mx-auto" : ""
                )}
              >
                <Balancer>{data.subheading}</Balancer>
              </p>
            ) : null}

            <div
              className={clsx(
                "flex flex-wrap items-center gap-4 pt-2",
                align === "center" ? "justify-center" : "justify-start"
              )}
            >
              {data?.primary?.label ? (
                <Button
                  as={Link}
                  href={data.primary.href || "#lead"}
                  target={data.primary.target}
                  rel={data.primary.rel}
                  className="btn btn-lg btn-primary group shadow-lg shadow-primary/20 hover:-translate-y-1 hover:shadow-xl hover:shadow-primary/30 active:translate-y-0 active:shadow-lg transition-all"
                >
                  {data.primary.label}
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
                    className="ml-2 h-5 w-5 transition-transform group-hover:translate-x-1"
                  >
                    <path d="M5 12h14" />
                    <path d="m12 5 7 7-7 7" />
                  </svg>
                </Button>
              ) : null}

              {data?.secondary?.label ? (
                <Button
                  as={Link}
                  href={data.secondary.href || "#"}
                  target={data.secondary.target}
                  rel={data.secondary.rel}
                  className="btn btn-lg btn-outline"
                >
                  {data.secondary.label}
                </Button>
              ) : null}
            </div>
          </motion.div>

          {/* --- Image Section --- */}
          {img && (
            <motion.div
              initial={{ opacity: 0, y: 20, scale: 0.95 }}
              animate={{ opacity: 1, y: 0, scale: 1 }}
              transition={{ duration: 0.6, ease: "easeOut", delay: 0.1 }}
              className={clsx(align === "center" ? "mx-auto max-w-3xl" : "")}
            >
              <motion.div
                animate={{ y: [0, -8, 0] }}
                transition={{
                  duration: 8,
                  repeat: Infinity,
                  ease: "easeInOut",
                }}
                className="relative"
              >
                <Image
                  src={img}
                  alt={data.heading || "Hero Image"}
                  width={1280}
                  height={900}
                  priority
                  className="w-full h-auto rounded-2xl shadow-2xl"
                  sizes="(min-width: 1024px) 50vw, 100vw"
                />
                <div className="pointer-events-none absolute inset-0 rounded-2xl ring-1 ring-primary/20" />
              </motion.div>
            </motion.div>
          )}
        </div>

        {/* --- Optional Features Section --- */}
        {Array.isArray(data?.features) && data.features.length > 0 && (
          <div className="pt-16 sm:pt-24">
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
              {data.features.map((feature, i) => (
                <motion.div
                  key={`${feature.title}-${i}`}
                  initial={{ opacity: 0, y: 20 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  viewport={{ once: true, amount: 0.5 }}
                  transition={{ duration: 0.5, delay: i * 0.1 }}
                >
                  <Card className="card-interactive h-full">
                    <CardBody className="p-6">
                      <h3 className="font-bold">{feature.title}</h3>
                      {feature.desc ? (
                        <p className="mt-2">{feature.desc}</p>
                      ) : null}
                    </CardBody>
                  </Card>
                </motion.div>
              ))}
            </div>
          </div>
        )}
      </div>
    </section>
  );
}

