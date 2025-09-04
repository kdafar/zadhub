"use client";

import { HeroUIProvider } from "@heroui/react";
import { ReactNode } from "react";

export default function Providers({ children }: { children: ReactNode }) {
  // The provider component wraps your entire application, enabling the UI library.
  return <HeroUIProvider>{children}</HeroUIProvider>;
}
