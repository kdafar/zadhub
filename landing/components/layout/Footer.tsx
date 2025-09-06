"use client";

import Link from "next/link";
import Image from "next/image";
import { useMemo } from "react";

// --- Type Definitions ---
type Locale = "en" | "ar";

// --- SVG Icons for Social Media ---
const SocialIcon = ({ href, children }: { href: string; children: React.ReactNode }) => (
  <a
    href={href}
    target="_blank"
    rel="noopener noreferrer"
    className="text-muted-foreground hover:text-primary transition-colors"
  >
    {children}
  </a>
);

// --- Main Component ---
export default function Footer({ locale }: { locale: Locale }) {
  const year = new Date().getFullYear();

  // Memoized translations for performance
  const t = useMemo(
    () => ({
      product: locale === "ar" ? "المنتج" : "Product",
      company: locale === "ar" ? "الشركة" : "Company",
      legal: locale === "ar" ? "قانوني" : "Legal",
      features: locale === "ar" ? "المزايا" : "Features",
      pricing: locale === "ar" ? "الأسعار" : "Pricing",
      faq: locale === "ar" ? "الأسئلة الشائعة" : "FAQ",
      contact: locale === "ar" ? "تواصل معنا" : "Contact",
      privacy: locale === "ar" ? "الخصوصية" : "Privacy",
      terms: locale === "ar" ? "الشروط" : "Terms",
      tagline: locale === "ar" ? "حلول واتساب للأعمال في الكويت" : "WhatsApp Business automation for Kuwait",
    }),
    [locale]
  );

  // Construct the absolute URL for the logo
  const siteUrl = process.env.NEXT_PUBLIC_SITE_URL || "https://zad-hub.com";
  const logoSrc = `${siteUrl}/storage/landing/logo.jpeg`;

  return (
    <footer className="bg-foreground text-background">
      <div className="container section-padding-sm">
        <div className="grid gap-8 grid-cols-2 md:grid-cols-4 lg:grid-cols-5">
          {/* Logo and Tagline */}
          <div className="col-span-2 lg:col-span-2">
            <Link href={`/${locale}`} className="flex items-center gap-3">
              <Image
                src={logoSrc}
                alt="Zad Logo"
                width={32}
                height={32}
                className="rounded-lg"
              />
              <span className="font-display text-xl font-bold tracking-tight">Zad</span>
            </Link>
            <p className="mt-4 text-muted-foreground">{t.tagline}</p>
          </div>

          {/* Product Links */}
          <div>
            <h4 className="font-bold mb-4">{t.product}</h4>
            <ul className="space-y-3 text-sm">
              <li><a href="#features">{t.features}</a></li>
              <li><a href="#pricing">{t.pricing}</a></li>
              <li><a href="#faq">{t.faq}</a></li>
            </ul>
          </div>

          {/* Company Links */}
          <div>
            <h4 className="font-bold mb-4">{t.company}</h4>
            <ul className="space-y-3 text-sm">
              <li><a href="#lead">{t.contact}</a></li>
            </ul>
          </div>

          {/* Legal Links */}
          <div>
            <h4 className="font-bold mb-4">{t.legal}</h4>
            <ul className="space-y-3 text-sm">
              <li><Link href="/privacy">{t.privacy}</Link></li>
              <li><Link href="/terms">{t.terms}</Link></li>
            </ul>
          </div>
        </div>

        {/* Bottom Bar */}
        <div className="mt-12 pt-8 border-t border-border/20">
          <div className="flex flex-col-reverse items-center gap-6 sm:flex-row sm:justify-between">
            <p className="text-sm text-muted-foreground">
              © {year} Zad Hub. All rights reserved.
            </p>
            {/* Add your social media links here */}
            <div className="flex items-center gap-5">
              <SocialIcon href="#">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" /></svg>
              </SocialIcon>
              <SocialIcon href="#">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z" /><rect x="2" y="9" width="4" height="12" /><circle cx="4" cy="4" r="2" /></svg>
              </SocialIcon>
              <SocialIcon href="#">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z" /></svg>
              </SocialIcon>
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
}
