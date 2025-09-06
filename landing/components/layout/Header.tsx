"use client";

import Link from "next/link";
import Image from "next/image";
import {
  Navbar,
  NavbarBrand,
  NavbarContent,
  NavbarItem,
  NavbarMenu,
  NavbarMenuItem,
  NavbarMenuToggle,
  Button,
} from "@heroui/react";
import { useMemo, useState } from "react";
import LocaleSwitch from "../LocaleSwitch";

type Locale = "en" | "ar";

export default function Header({ locale }: { locale: Locale }) {
  const [open, setOpen] = useState(false);
  const isRTL = locale === "ar";

  const t = useMemo(
    () => ({
      features: isRTL ? "المزايا" : "Features",
      industries: isRTL ? "القطاعات" : "Industries",
      faq: isRTL ? "الأسئلة الشائعة" : "FAQ",
      demo: isRTL ? "احجز عرضًا" : "Get a Demo",
      waDemo: isRTL ? "تجربة واتساب" : "WhatsApp Demo",
      brand: "Zad",
      logoAlt: isRTL ? "شعار زاد" : "Zad Logo",
      menuOpen: isRTL ? "فتح القائمة" : "Open menu",
      menuClose: isRTL ? "إغلاق القائمة" : "Close menu",
    }),
    [isRTL]
  );

  const links = [
    { href: "#features", label: t.features },
    { href: "#industries", label: t.industries },
    { href: "#faq", label: t.faq },
  ];

  const waNumber = (process.env.NEXT_PUBLIC_WHATSAPP_NUMBER || "96500000000").replace(/\D/g, "");
  const waText = isRTL ? "أرغب في تجربة بوت واتساب" : "I'd like a WhatsApp demo";
  const waHref = `https://wa.me/${waNumber}?text=${encodeURIComponent(waText)}`;

  const siteUrl = process.env.NEXT_PUBLIC_SITE_URL || "https://zad-hub.com";
  const logoSrc = `${siteUrl}/storage/landing/logo.jpeg`;

  return (
    <header dir={isRTL ? "rtl" : "ltr"} className="sticky top-0 z-50">
      <Navbar
        isMenuOpen={open}
        onMenuOpenChange={setOpen}
        shouldHideOnScroll
        maxWidth="xl"
        classNames={{
          base:
            "backdrop-blur supports-[backdrop-filter]:bg-background/70 bg-background/90 border-b border-border",
          wrapper: "container h-16 sm:h-20 px-3 sm:px-4",
          menu: "md:hidden px-6 py-6 bg-background/95",
        }}
      >
        {/* Right in RTL (Left in LTR): Brand + burger */}
        <NavbarContent
          justify="start"
          className="flex-1 min-w-0 items-center gap-2 md:gap-3 pe-2 sm:pe-4"
        >
          <NavbarMenuToggle
            aria-label={open ? t.menuClose : t.menuOpen}
            className="md:hidden"
          />
          <NavbarBrand className="min-w-max">
            <Link href={`/${locale}`} className="flex items-center gap-3">
              <Image
                src={logoSrc}
                alt={t.logoAlt}
                width={36}
                height={36}
                priority
                className="rounded-xl"
              />
              <span className="font-display text-lg sm:text-xl font-bold tracking-tight text-foreground">
                {t.brand}
              </span>
            </Link>
          </NavbarBrand>
        </NavbarContent>

        {/* Center: Primary nav (desktop) */}
        <NavbarContent justify="center" className="hidden md:flex mx-2 lg:mx-8 xl:mx-12">
          <nav
            aria-label="Primary"
            className="flex items-center gap-5 lg:gap-7 xl:gap-10"
          >
            {links.map((l) => (
              <NavbarItem key={l.href}>
                <a
                  href={l.href}
                  className="group relative px-1 text-sm lg:text-[15px] text-foreground/80 hover:text-foreground transition-colors font-medium"
                >
                  {l.label}
                  <span className="absolute -bottom-1 left-0 h-0.5 w-full origin-left scale-x-0 bg-primary transition-transform duration-300 ease-out group-hover:scale-x-100" />
                </a>
              </NavbarItem>
            ))}
          </nav>
        </NavbarContent>

        {/* Left in RTL (Right in LTR): Actions */}
        <NavbarContent
          justify="end"
          className="flex-1 min-w-0 items-center gap-2 sm:gap-3 lg:gap-4 xl:gap-6"
        >
          {/* Locale switch */}
          <NavbarItem className="hidden lg:flex">
            <div className="px-1 sm:px-2">
              <LocaleSwitch locale={locale} />
            </div>
          </NavbarItem>

          {/* WhatsApp Demo – bordered pill, with icon */}
          <NavbarItem className="hidden sm:flex">
            <Button
              as="a"
              href={waHref}
              target="_blank"
              rel="noopener noreferrer"
              radius="full"
              variant="bordered"
              className="px-4 sm:px-5 py-2 text-sm lg:text-[15px]"
              startContent={
                <svg
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                  fill="currentColor"
                  aria-hidden="true"
                >
                  <path d="M12.04 2C6.58 2 2.16 6.42 2.16 11.88c0 2.1.6 4.05 1.66 5.7L2 22l4.56-1.76c1.58.86 3.38 1.36 5.28 1.36 5.46 0 9.88-4.42 9.88-9.88S17.5 2 12.04 2zm5.77 14.1c-.24.68-1.2 1.24-1.96 1.4-.52.1-1.2.18-3.5-.74-2.94-1.2-4.82-4.15-4.96-4.35-.14-.2-1.18-1.58-1.18-3.02 0-1.44.74-2.14 1-2.44.24-.3.64-.44 1.02-.44.12 0 .22 0 .32.02.28.02.42.04.6.46.24.56.82 1.96.9 2.1.08.14.12.3.02.48-.1.2-.16.3-.32.46-.16.16-.34.36-.48.48-.16.16-.34.34-.14.64.2.3.88 1.44 1.9 2.34 1.3 1.16 2.4 1.52 2.76 1.68.34.14.54.12.74-.06.22-.24.84-1 .1-2.06-.74-1.06-1.64-1.36-1.88-1.48-.24-.12-.38-.18-.28-.38.1-.2.44-1.08.6-1.46.16-.38.32-.32.56-.32.14 0 .3 0 .46.02.16.02.42.04.64.5.22.46.76 1.8.82 1.94.06.14.08.26.02.4z" />
                </svg>
              }
            >
              {t.waDemo}
            </Button>
          </NavbarItem>

          {/* Primary CTA – solid pill, with arrow */}
          <NavbarItem>
            <Button
              as={Link}
              href={`/${locale}#lead`}
              radius="full"
              color="primary"
              className="px-4 sm:px-5 py-2 text-sm lg:text-[15px] hover:opacity-90"
              endContent={
                <svg
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                  fill="currentColor"
                  aria-hidden="true"
                >
                  {isRTL ? (
                    <path d="M14.7 6.3a1 1 0 0 1 1.4 1.4L13.83 10H20a1 1 0 1 1 0 2h-6.17l2.27 2.3a1 1 0 1 1-1.42 1.4l-4-4a1 1 0 0 1 0-1.4l4-4z" />
                  ) : (
                    <path d="M9.3 6.3a1 1 0 0 1 1.4 0l4 4a1 1 0 0 1 0 1.4l-4 4a1 1 0 1 1-1.4-1.4L12.17 12H6a1 1 0 1 1 0-2h6.17L9.3 7.7a1 1 0 0 1 0-1.4z" />
                  )}
                </svg>
              }
            >
              {t.demo}
            </Button>
          </NavbarItem>
        </NavbarContent>

        {/* Mobile menu */}
        <NavbarMenu>
          <div className="space-y-2">
            {links.map((l) => (
              <NavbarMenuItem key={l.href}>
                <a
                  href={l.href}
                  className="block w-full py-3 text-xl font-display font-semibold text-foreground"
                  onClick={() => setOpen(false)}
                >
                  {l.label}
                </a>
              </NavbarMenuItem>
            ))}
          </div>

          <div className="mt-6 border-t border-border pt-6 space-y-4">
            <div className="flex justify-center">
              <LocaleSwitch locale={locale} />
            </div>

            <Button
              as="a"
              href={waHref}
              target="_blank"
              rel="noopener noreferrer"
              radius="full"
              variant="bordered"
              className="w-full py-3 text-base"
              startContent={
                <svg
                  width="18"
                  height="18"
                  viewBox="0 0 24 24"
                  fill="currentColor"
                  aria-hidden="true"
                >
                  <path d="M12.04 2C6.58 2 2.16 6.42 2.16 11.88c0 2.1.6 4.05 1.66 5.7L2 22l4.56-1.76c1.58.86 3.38 1.36 5.28 1.36 5.46 0 9.88-4.42 9.88-9.88S17.5 2 12.04 2zm5.77 14.1c-.24.68-1.2 1.24-1.96 1.4-.52.1-1.2.18-3.5-.74-2.94-1.2-4.82-4.15-4.96-4.35-.14-.2-1.18-1.58-1.18-3.02 0-1.44.74-2.14 1-2.44.24-.3.64-.44 1.02-.44.12 0 .22 0 .32.02.28.02.42.04.6.46.24.56.82 1.96.9 2.1.08.14.12.3.02.48-.1.2-.16.3-.32.46-.16.16-.34.36-.48.48-.16.16-.34.34-.14.64.2.3.88 1.44 1.9 2.34 1.3 1.16 2.4 1.52 2.76 1.68.34.14.54.12.74-.06.22-.24.84-1 .1-2.06-.74-1.06-1.64-1.36-1.88-1.48-.24-.12-.38-.18-.28-.38.1-.2.44-1.08.6-1.46.16-.38.32-.32.56-.32.14 0 .3 0 .46.02.16.02.42.04.64.5.22.46.76 1.8.82 1.94.06.14.08.26.02.4z" />
                </svg>
              }
              onClick={() => setOpen(false)}
            >
              {t.waDemo}
            </Button>

            <Button
              as={Link}
              href={`/${locale}#lead`}
              radius="full"
              color="primary"
              className="w-full py-3 text-base hover:opacity-90"
              endContent={
                <svg
                  width="18"
                  height="18"
                  viewBox="0 0 24 24"
                  fill="currentColor"
                  aria-hidden="true"
                >
                  {isRTL ? (
                    <path d="M14.7 6.3a1 1 0 0 1 1.4 1.4L13.83 10H20a1 1 0 1 1 0 2h-6.17l2.27 2.3a1 1 0 1 1-1.42 1.4l-4-4a1 1 0 0 1 0-1.4l4-4z" />
                  ) : (
                    <path d="M9.3 6.3a1 1 0 0 1 1.4 0l4 4a1 1 0 0 1 0 1.4l-4 4a1 1 0 1 1-1.4-1.4L12.17 12H6a1 1 0 1 1 0-2h6.17L9.3 7.7a1 1 0 0 1 0-1.4z" />
                  )}
                </svg>
              }
              onClick={() => setOpen(false)}
            >
              {t.demo}
            </Button>
          </div>
        </NavbarMenu>
      </Navbar>
    </header>
  );
}
