"use client";

import Link from "next/link";
import { Navbar, NavbarBrand, NavbarContent, NavbarItem, NavbarMenu, NavbarMenuToggle, NavbarMenuItem, Button } from "@heroui/react";
import { useState } from "react";
import LocaleSwitch from "../LocaleSwitch";

export default function Header({ locale }: { locale: "en" | "ar" }) {
  const [isMenuOpen, setIsMenuOpen] = useState(false);

  const links = [
    { href: "#features", label: locale === "ar" ? "المزايا" : "Features" },
    { href: "#industries", label: locale === "ar" ? "القطاعات" : "Industries" },
    { href: "#faq", label: locale === "ar" ? "الأسئلة الشائعة" : "FAQ" },
  ];

  return (
    <Navbar
      maxWidth="xl"
      isBordered
      onMenuOpenChange={setIsMenuOpen}
      classNames={{
        base: "bg-background/80 backdrop-blur-md",
        wrapper: "container", // Use the custom container class
      }}
    >
      <NavbarContent>
        <NavbarMenuToggle
          aria-label={isMenuOpen ? "Close menu" : "Open menu"}
          className="sm:hidden"
        />
        <NavbarBrand>
          <Link href={`/${locale}` as any} className="flex items-center gap-2">
            {/* Logo with theme-based gradient */}
            <span className="inline-block h-6 w-6 rounded-lg bg-gradient-to-br from-primary to-orange-400" />
            <span className="font-bold tracking-tight text-foreground">Zad</span>
          </Link>
        </NavbarBrand>
      </NavbarContent>

      <NavbarContent className="hidden sm:flex gap-6" justify="center">
        {links.map((l) => (
          <NavbarItem key={l.href}>
            {/* Links using semantic theme colors */}
            <a
              href={l.href}
              className="text-foreground/70 hover:text-foreground font-medium transition-colors"
            >
              {l.label}
            </a>
          </NavbarItem>
        ))}
      </NavbarContent>

      <NavbarContent justify="end">
        <NavbarItem className="hidden sm:flex">
          <LocaleSwitch locale={locale} />
        </NavbarItem>
        <NavbarItem>
          {/* Button correctly uses primary color from the theme */}
          <Button
            as={Link}
            href={`/${locale}#lead` as any}
            color="primary"
            radius="lg"
            className="font-semibold"
          >
            {locale === "ar" ? "احجز عرضًا" : "Get a Demo"}
          </Button>
        </NavbarItem>
      </NavbarContent>

      {/* Mobile Menu */}
      <NavbarMenu>
        {links.map((l) => (
          <NavbarMenuItem key={l.href}>
            <a
              href={l.href}
              className="block w-full py-2 text-lg text-foreground"
              onClick={() => setIsMenuOpen(false)}
            >
              {l.label}
            </a>
          </NavbarMenuItem>
        ))}
        <NavbarMenuItem>
          <LocaleSwitch locale={locale} />
        </NavbarMenuItem>
      </NavbarMenu>
    </Navbar>
  );
}
