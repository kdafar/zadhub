// tailwind.config.ts
import { heroui } from "@heroui/react";
import type { Config } from "tailwindcss";
import { fontFamily } from "tailwindcss/defaultTheme";

const config: Config = {
  darkMode: "class",
  content: [
    "./app/**/*.{ts,tsx}",
    "./components/**/*.{ts,tsx}",
    "./lib/**/*.{ts,tsx}",
    "./node_modules/@heroui/react/dist/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    container: { center: true, padding: "1rem" },
    extend: {
      // CSS variable-driven palette (match your globals.css tokens)
      colors: {
        border: "hsl(var(--border))",
        input: "hsl(var(--input))",
        ring: "hsl(var(--ring))",
        background: "hsl(var(--background))",
        foreground: "hsl(var(--foreground))",

        primary: {
          DEFAULT: "hsl(var(--primary))",
          foreground: "hsl(var(--primary-foreground))",
        },
        secondary: {
          DEFAULT: "hsl(var(--secondary))",
          foreground: "hsl(var(--secondary-foreground))",
        },
        muted: {
          DEFAULT: "hsl(var(--muted))",
          foreground: "hsl(var(--muted-foreground))",
        },
        accent: {
          DEFAULT: "hsl(var(--accent))",
          foreground: "hsl(var(--accent-foreground))",
        },
        destructive: {
          DEFAULT: "hsl(var(--destructive))",
          foreground: "hsl(var(--destructive-foreground))",
        },
        popover: {
          DEFAULT: "hsl(var(--popover))",
          foreground: "hsl(var(--popover-foreground))",
        },
        card: {
          DEFAULT: "hsl(var(--card))",
          foreground: "hsl(var(--card-foreground))",
        },

        // custom palette (kept as-is)
        "oxford-blue": {
          DEFAULT: "#14213d",
          100: "#04070c",
          200: "#080d19",
          300: "#0c1425",
          400: "#101b31",
          500: "#14213d",
          600: "#29447e",
          700: "#3e67bf",
          800: "#7e99d5",
          900: "#beccea",
        },
        "orange-web": {
          DEFAULT: "#fca311",
          100: "#362101",
          200: "#6b4201",
          300: "#a16402",
          400: "#d68502",
          500: "#fca311",
          600: "#fdb541",
          700: "#fec871",
          800: "#fedaa0",
          900: "#ffedd0",
        },
        platinum: {
          DEFAULT: "#e5e5e5",
          100: "#2e2e2e",
          200: "#5c5c5c",
          300: "#8a8a8a",
          400: "#b8b8b8",
          500: "#e5e5e5",
          600: "#ebebeb",
          700: "#f0f0f0",
          800: "#f5f5f5",
          900: "#fafafa",
        },
      },

      borderRadius: {
        lg: "var(--radius)",
        md: "calc(var(--radius) - 2px)",
        sm: "calc(var(--radius) - 4px)",
      },

      // Map Tailwind font families to Next Font CSS variables
      fontFamily: {
        sans: ["var(--font-sans)", ...fontFamily.sans],
        display: ["var(--font-display)", ...fontFamily.sans],
        arabic: ["var(--font-arabic)", ...fontFamily.sans], // used when dir=rtl
      },
    },
  },
  plugins: [
    heroui({
      themes: {
        light: {
          colors: {
            background: "#ffffff",
            foreground: "#14213d",
            primary: { DEFAULT: "#fca311", foreground: "#14213d" },
            secondary: { DEFAULT: "#e5e5e5", foreground: "#14213d" },
            focus: "#fca311",
          },
        },
        // add dark here if needed
      },
    }) as any,
    require("tailwindcss-animate"),
    require("@tailwindcss/container-queries"),
    require("@tailwindcss/typography"),
  ],
};

export default config;
