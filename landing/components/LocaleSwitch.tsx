'use client';

import { usePathname, useSearchParams, useRouter } from 'next/navigation';
import type { Route } from 'next';

type Locale = 'en' | 'ar';
const LOCALE_RE = /^\/(en|ar)(?=\/|$)/;

function setLocaleCookie(next: Locale) {
  try {
    // 1 year, lax, site-wide
    document.cookie = `locale=${next}; path=/; max-age=${60 * 60 * 24 * 365}; samesite=lax`;
  } catch {}
}

export default function LocaleSwitch({ locale }: { locale: Locale }) {
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();

  const goTo = (nextLocale: Locale) => {
    if (!pathname) return;

    // strip current /en or /ar prefix
    const match = pathname.match(LOCALE_RE);
    const rest = match ? pathname.slice(match[0].length) : pathname;

    // keep other params, drop ?locale
    const params = new URLSearchParams(searchParams);
    params.delete('locale');
    const qs = params.toString();

    // preserve hash if present
    const hash = typeof window !== 'undefined' ? window.location.hash : '';

    const nextPath = (`/${nextLocale}${rest}${qs ? `?${qs}` : ''}${hash}`) as Route;

    setLocaleCookie(nextLocale);
    router.push(nextPath, { scroll: false });
  };

  const isAR = locale === 'ar';
  const isEN = locale === 'en';

  return (
    <div
      role="group"
      aria-label={isAR ? 'تبديل اللغة' : 'Language switch'}
      className="inline-flex items-center rounded-full border border-border bg-background/70 p-0.5 shadow-sm"
    >
      <button
        type="button"
        aria-pressed={isAR}
        onClick={() => goTo('ar')}
        className={[
          'px-3 py-1.5 text-xs font-medium transition',
          'rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/50',
          isAR
            ? 'bg-primary text-primary-foreground shadow'
            : 'text-foreground/80 hover:bg-foreground/5'
        ].join(' ')}
      >
        AR
      </button>

      <button
        type="button"
        aria-pressed={isEN}
        onClick={() => goTo('en')}
        className={[
          'px-3 py-1.5 text-xs font-medium transition',
          'rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/50',
          isEN
            ? 'bg-primary text-primary-foreground shadow'
            : 'text-foreground/80 hover:bg-foreground/5'
        ].join(' ')}
      >
        EN
      </button>
    </div>
  );
}
