import { NextRequest, NextResponse } from 'next/server';

const LOCALES = ['en', 'ar'] as const;
type Locale = (typeof LOCALES)[number];

function currentLocale(pathname: string): Locale | null {
  return (LOCALES.find(l => pathname === `/${l}` || pathname.startsWith(`/${l}/`)) ?? null) as Locale | null;
}

export function middleware(request: NextRequest) {
  const url = request.nextUrl;
  const desired = url.searchParams.get('locale') as Locale | null;

  if (desired && LOCALES.includes(desired)) {
    const cur = currentLocale(url.pathname);
    // compute the rest of the path after the current locale segment (if any)
    const rest = cur ? url.pathname.slice(('/' + cur).length) : url.pathname;

    if (cur !== desired) {
      url.searchParams.delete('locale'); // clean the query
      url.pathname = `/${desired}${rest || ''}`;
      return NextResponse.redirect(url);
    }
  }

  // otherwise let it through
  return NextResponse.next();
}

export const config = {
  matcher: [
    '/', // include root so "/?locale=ar" → "/ar"
    '/((?!api|_next/static|_next/image|favicon.ico|.*\\..*).*)',
  ],
};
