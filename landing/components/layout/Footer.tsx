type Locale = 'en' | 'ar'

export default function Footer({ locale }: { locale: Locale }) {
  const year = new Date().getFullYear()

  const t = {
    product: locale === 'ar' ? 'المنتج' : 'Product',
    company: locale === 'ar' ? 'الشركة' : 'Company',
    legal:   locale === 'ar' ? 'قانوني' : 'Legal',
    features: locale === 'ar' ? 'المزايا' : 'Features',
    pricing:  locale === 'ar' ? 'الأسعار' : 'Pricing',
    faq:      locale === 'ar' ? 'الأسئلة الشائعة' : 'FAQ',
    contact:  locale === 'ar' ? 'تواصل معنا' : 'Contact',
    privacy:  locale === 'ar' ? 'الخصوصية' : 'Privacy',
    terms:    locale === 'ar' ? 'الشروط' : 'Terms',
    tagline:  locale === 'ar' ? 'حلول واتساب للأعمال في الكويت' : 'WhatsApp Business automation for Kuwait',
  }

  return (
    <footer className="bg-slate-950 text-slate-200">
      <div className="container-pro py-12 grid gap-8 md:grid-cols-4">
        <div className="col-span-1">
          {/* Put your logo at /landing/public/logo.svg */}
          <img src="/logo.svg" alt="Logo" className="h-7 w-auto opacity-90" />
          <p className="text-sm opacity-70 mt-3">{t.tagline}</p>
        </div>

        <div>
          <h4 className="font-bold mb-3">{t.product}</h4>
          <ul className="space-y-2 text-sm">
            <li><a href="#features" className="hover:opacity-80">{t.features}</a></li>
            <li><a href="#pricing" className="hover:opacity-80">{t.pricing}</a></li>
            <li><a href="#faq" className="hover:opacity-80">{t.faq}</a></li>
          </ul>
        </div>

        <div>
          <h4 className="font-bold mb-3">{t.company}</h4>
          <ul className="space-y-2 text-sm">
            <li><a href="#lead" className="hover:opacity-80">{t.contact}</a></li>
          </ul>
        </div>

        <div>
          <h4 className="font-bold mb-3">{t.legal}</h4>
          <ul className="space-y-2 text-sm">
            <li><a href="/privacy" className="hover:opacity-80">{t.privacy}</a></li>
            <li><a href="/terms" className="hover:opacity-80">{t.terms}</a></li>
          </ul>
        </div>
      </div>

      <div className="border-t border-slate-800">
        <div className="container-pro py-6 text-xs opacity-70 flex items-center justify-between">
          <span>© {year} Zad Hub</span>
          <span className="ltr">Kuwait</span>
        </div>
      </div>
    </footer>
  )
}
