import { NextRequest, NextResponse } from 'next/server';
import Negotiator from 'negotiator';
import { match as matchLocale } from '@formatjs/intl-localematcher';

const locales = ['en', 'ar'];
const defaultLocale = 'en';

function getLocale(request: NextRequest): string {
  const negotiatorHeaders: Record<string, string> = {};
  request.headers.forEach((value, key) => (negotiatorHeaders[key] = value));

  const languages = new Negotiator({ headers: negotiatorHeaders }).languages();
  
  return matchLocale(languages, locales, defaultLocale);
}

export function middleware(request: NextRequest) {
  const pathname = request.nextUrl.pathname;

  // ✅ ADD THIS BLOCK
  // If the request is for the root path, always redirect to /en.
  if (pathname === '/') {
    return NextResponse.redirect(new URL('/en', request.url));
  }

  // Check if there is any supported locale in the pathname.
  const pathnameIsMissingLocale = locales.every(
    (locale) => !pathname.startsWith(`/${locale}/`) && pathname !== `/${locale}`
  );

  // Your existing i18n logic for all other pages
  if (pathnameIsMissingLocale) {
    const locale = getLocale(request);
    
    // NOTE: Because of the redirect above, this rewrite logic will now only apply
    // to paths like /about, /contact etc., but not the root /.
    if (locale === defaultLocale) {
      return NextResponse.rewrite(
        new URL(`/${defaultLocale}${pathname}`, request.url)
      );
    }

    return NextResponse.redirect(
      new URL(`/${locale}${pathname}`, request.url)
    );
  }
}

export const config = {
  matcher: [
    '/((?!api|_next/static|_next/image|favicon.ico|.*\\..*).*)',
  ],
};