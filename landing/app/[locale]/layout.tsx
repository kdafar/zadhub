import { ReactNode } from "react";
import Providers from "../providers";
import "../globals.css";
import { notFound } from "next/navigation";

export default async function LocaleLayout({
  children,
  params,
}: {
  children: ReactNode;
  params: Promise<{ locale: string }>; // ← accept string (matches Next’s generated types)
}) {
  const { locale: rawLocale } = await params;

  // Narrow to your supported locales
  const locale = rawLocale === "ar" ? "ar" : rawLocale === "en" ? "en" : null;
  if (!locale) notFound();

  const dir = locale === "ar" ? "rtl" : "ltr";

  return (
    <html lang={locale} dir={dir}>
      <body className="min-h-dvh bg-background text-foreground" suppressHydrationWarning>
        <Providers>{children}</Providers>
      </body>
    </html>
  );
}

// (Optional) pre-generate both locales
export async function generateStaticParams() {
  return [{ locale: "en" }, { locale: "ar" }];
}
