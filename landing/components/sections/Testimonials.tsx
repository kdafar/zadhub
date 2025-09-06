"use client";

import Image from "next/image";
import { motion } from "framer-motion";
import { resolveAssetUrl } from "@/lib/url";
import { Avatar, Button } from "@heroui/react";
import useEmblaCarousel from "embla-carousel-react";
import { useCallback } from "react";

// --- Type Definitions ---
type Item = {
  quote: string;
  author: string;
  role?: string;
  avatar?: string;
  rating?: number;
};

type TestimonialsProps = {
  data: {
    eyebrow?: string;
    heading?: string;
    subheading?: string;
    items: Item[];
  };
};

// --- SVG Star Icon ---
const StarIcon = ({ filled }: { filled: boolean }) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width="20"
    height="20"
    viewBox="0 0 24 24"
    strokeWidth="2"
    strokeLinecap="round"
    strokeLinejoin="round"
    className={`
      ${filled ? "fill-primary stroke-primary" : "fill-transparent stroke-muted-foreground/50"}
    `}
  >
    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
  </svg>
);

// --- Star Rating Component ---
const StarRating = ({ rating }: { rating: number }) => (
  <div className="flex items-center gap-1">
    {Array.from({ length: 5 }).map((_, i) => (
      <StarIcon key={i} filled={i < rating} />
    ))}
  </div>
);

// --- Main Component ---
export default function Testimonials({ data }: TestimonialsProps) {
  const items = data?.items || [];
  const [emblaRef, emblaApi] = useEmblaCarousel({ loop: true, align: "start" });

  const scrollPrev = useCallback(() => {
    if (emblaApi) emblaApi.scrollPrev();
  }, [emblaApi]);

  const scrollNext = useCallback(() => {
    if (emblaApi) emblaApi.scrollNext();
  }, [emblaApi]);

  if (!items.length) return null;

  return (
    <section id="testimonials" className="bg-secondary text-foreground section-padding">
      <div className="container">
        {/* --- Section Heading --- */}
        <div className="section-heading">
          {data.eyebrow && <span className="eyebrow">{data.eyebrow}</span>}
          {data.heading && <h2 className="heading">{data.heading}</h2>}
          {data.subheading && <p className="subheading">{data.subheading}</p>}
        </div>

        {/* --- Testimonials Carousel --- */}
        <div className="relative mt-12">
          <div className="overflow-hidden" ref={emblaRef}>
            <div className="flex">
              {items.map((testimonial, i) => (
                <div key={i} className="flex-[0_0_100%] md:flex-[0_0_50%] lg:flex-[0_0_33.33%] min-w-0 pl-4">
                  <motion.figure
                    initial={{ opacity: 0, y: 20 }}
                    whileInView={{ opacity: 1, y: 0 }}
                    viewport={{ once: true, amount: 0.3 }}
                    transition={{ duration: 0.6, delay: i * 0.1 }}
                    className="card card-hover flex h-full flex-col p-6"
                  >
                    {/* Author Info */}
                    <div className="flex items-center gap-4">
                      <Avatar
                        src={resolveAssetUrl(testimonial.avatar)}
                        name={testimonial.author}
                        size="lg"
                      />
                      <div>
                        <figcaption className="font-semibold">{testimonial.author}</figcaption>
                        {testimonial.role && (
                          <div className="text-sm text-muted-foreground">{testimonial.role}</div>
                        )}
                      </div>
                    </div>
                    {/* Rating */}
                    {typeof testimonial.rating === "number" && testimonial.rating > 0 && (
                      <div className="mt-4">
                        <StarRating rating={testimonial.rating} />
                      </div>
                    )}
                    {/* Quote */}
                    <blockquote className="mt-4 text-foreground/90 flex-grow">
                      <p>“{testimonial.quote}”</p>
                    </blockquote>
                  </motion.figure>
                </div>
              ))}
            </div>
          </div>
          {/* Carousel Controls */}
          <div className="mt-8 flex justify-center gap-4">
            <Button isIconOnly onClick={scrollPrev} className="btn-outline !rounded-full !w-12 !h-12">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
            </Button>
            <Button isIconOnly onClick={scrollNext} className="btn-outline !rounded-full !w-12 !h-12">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </Button>
          </div>
        </div>
      </div>
    </section>
  );
}

