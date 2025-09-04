// app/layout.tsx
import type { ReactNode } from "react";
import "./globals.css";
import Providers from "./providers";
import { cookies } from "next/headers";

export default async function RootLayout({ children }: { children: ReactNode }) {
  const cookieStore = await cookies();
  const locale = (cookieStore.get("locale")?.value as "en" | "ar") ?? "en";
  const dir = locale === "ar" ? "rtl" : "ltr";

  return (
    <html lang={locale} dir={dir} className="zad" key={locale}>
      <head />
      <body className="min-h-dvh bg-white text-[color:var(--oxford-blue)]">
        <Providers>{children}</Providers>
      </body>
    </html>
  );
}
