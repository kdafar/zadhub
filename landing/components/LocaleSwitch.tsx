'use client'

import { usePathname, useSearchParams, useRouter } from 'next/navigation'
import type { Route } from 'next'; // 👈 Import the Route type

type Locale = 'en' | 'ar'

export default function LocaleSwitch({ locale: initialLocale }: { locale: Locale }) {
  const router = useRouter();
  const searchParams = useSearchParams();
  const pathname = usePathname();

  const other: Locale = initialLocale === 'ar' ? 'en' : 'ar';
  
  const handleLocaleChange = () => {
    const newParams = new URLSearchParams(searchParams.toString());
    newParams.set('locale', other);

    // Create the new URL string and assert its type as a valid Route
    const newUrl = `${pathname}?${newParams.toString()}` as Route;
    
    // Navigate using the type-asserted URL
    router.push(newUrl);
  };
  
  return (
    <button onClick={handleLocaleChange} className="text-sm opacity-80 hover:opacity-100">
      {other.toUpperCase()}
    </button>
  );
}