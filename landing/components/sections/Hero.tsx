"use client";

import Image from "next/image";
import Link from "next/link";
import { motion } from "framer-motion";
import { Button, Chip, Card, CardBody } from "@heroui/react"; // Updated import
import clsx from "clsx";
import { resolveAssetUrl } from "@/lib/url";

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

export default function Hero({ data }: { data: Data }) {
  const img = resolveAssetUrl?.(data?.image ?? "") || undefined;
  const align = data?.align ?? "left";

  return (
    <section className="text-[color:var(--oxford-blue)] bg-white">
      <div className="relative overflow-hidden">
        {/* Light brand gradient */}
        <div aria-hidden className="pointer-events-none absolute inset-0 -z-10 bg-hero-brand" />

        <div className="relative container mx-auto px-4 sm:px-6 lg:px-8 py-18 sm:py-24 grid lg:grid-cols-2 gap-12 items-center">
          {/* Copy */}
          <motion.div
            initial={{ opacity: 0, y: 14 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5, ease: "easeOut" }}
            className={clsx("space-y-6", align === "center" ? "text-center" : "lg:text-left text-left")}
          >
            {data?.eyebrow ? (
              <Chip
                color="secondary"
                variant="flat"
                className="uppercase tracking-[.25em] text-[color:var(--oxford-blue)]"
              >
                {data.eyebrow}
              </Chip>
            ) : null}

            {data?.heading ? (
              <h1 className="text-4xl md:text-5xl font-extrabold leading-tight [text-wrap:balance]">
                <span className="bg-clip-text text-transparent bg-gradient-to-r from-[var(--orange-web)] to-[var(--oxford-blue)]">
                  {/* You can place emphasized words here if needed */}
                </span>
                {data.heading}
              </h1>
            ) : null}

            {data?.subheading ? (
              <p className={clsx("text-base md:text-lg opacity-90 [text-wrap:pretty]", align === "center" ? "mx-auto max-w-2xl" : "")}>
                {data.subheading}
              </p>
            ) : null}

            <div className={clsx("flex flex-wrap gap-3 pt-1", align === "center" ? "justify-center" : "justify-start")}>
              {data?.primary?.label ? (
                <Button
                  as={Link}
                  href={data.primary.href || "#lead"}
                  target={data.primary.target}
                  rel={data.primary.rel}
                  color="primary" // maps to --orange-web
                  radius="lg"
                  size="lg"
                  className="font-semibold"
                >
                  {data.primary.label}
                </Button>
              ) : null}

              {data?.secondary?.label ? (
                <Button
                  as={Link}
                  href={data.secondary.href || "#"}
                  target={data.secondary.target}
                  rel={data.secondary.rel}
                  variant="bordered"
                  radius="lg"
                  size="lg"
                >
                  {data.secondary.label}
                </Button>
              ) : null}
            </div>
          </motion.div>

          {/* Image */}
          <motion.div
            initial={{ opacity: 0, y: 16, scale: 0.98 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            transition={{ duration: 0.6, ease: "easeOut", delay: 0.05 }}
            className={clsx(align === "center" ? "mx-auto max-w-3xl" : "")}
          >
            {img ? (
              <motion.div
                animate={{ y: [0, -6, 0] }}
                transition={{ duration: 6, repeat: Infinity, ease: "easeInOut" }}
                className="relative"
              >
                <Image
                  src={img}
                  alt=""
                  width={1280}
                  height={900}
                  priority
                  className="w-full h-auto rounded-3xl shadow-lg"
                  sizes="(min-width: 1024px) 50vw, 100vw"
                />
                <div className="pointer-events-none absolute inset-0 rounded-3xl ring-1 ring-[var(--orange-web)] [--tw-ring-opacity:0.35]" />
              </motion.div>
            ) : null}
          </motion.div>
        </div>

        {/* Optional features */}
        {Array.isArray(data?.features) && data.features.length > 0 ? (
          <div className="relative container mx-auto px-4 sm:px-6 lg:px-8 pb-14">
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
              {data.features.map((f, i) => (
                <Card
                  key={`${f.title}-${i}`}
                  shadow="sm"
                  radius="lg"
                  className="border border-[color:var(--platinum)] hover:shadow-md transition-shadow"
                >
                  <CardBody className="p-6">
                    <h3 className="font-semibold text-[color:var(--oxford-blue)]">{f.title}</h3>
                    {f.desc ? <p className="text-slate-700 mt-1">{f.desc}</p> : null}
                  </CardBody>
                </Card>
              ))}
            </div>
          </div>
        ) : null}
      </div>
    </section>
  );
}
