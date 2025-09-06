// app/layout.tsx
import type { ReactNode } from "react";
import "./globals.css";
import Providers from "./providers";
import { cookies } from "next/headers";
import { Plus_Jakarta_Sans, Figtree, Tajawal } from "next/font/google";

const fontSans = Figtree({ subsets: ["latin"], variable: "--font-sans" });
const fontDisplay = Plus_Jakarta_Sans({ subsets: ["latin"], variable: "--font-display" });
const fontArabic = Tajawal({ subsets: ["arabic"], weight: "400", variable: "--font-arabic" });

export default async function RootLayout({ children }: { children: ReactNode }) {
  const cookieStore = await cookies();
  const locale = (cookieStore.get("locale")?.value as "en" | "ar") ?? "en";
  const dir = locale === "ar" ? "rtl" : "ltr";

  return (
    <html
      lang={locale}
      dir={dir}
      className={`${fontSans.variable} ${fontDisplay.variable} ${fontArabic.variable} zad`}
      key={locale}
    >
      <head />
      <body
        className={`min-h-dvh bg-background text-foreground antialiased ${
          dir === "rtl" ? "font-arabic" : "font-sans"
        }`}
      >
        <Providers>{children}</Providers>
      </body>
    </html>
  );
}
