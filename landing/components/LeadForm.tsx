'use client'

import { useState, useMemo } from 'react'

type Locale = 'en'|'ar'

const USE_CASE_OPTIONS = [
  { value: 'restaurant', labelEn: 'Restaurant',          labelAr: 'مطاعم' },
  { value: 'pharmacy',   labelEn: 'Pharmacy',            labelAr: 'صيدلية' },
  { value: 'grocery',    labelEn: 'Grocery / Retail',    labelAr: 'بقالة / تجزئة' },
  { value: 'logistics',  labelEn: 'Logistics / Services',labelAr: 'خدمات / لوجستيات' },
  { value: 'other',      labelEn: 'Other',               labelAr: 'أخرى' },
]

export default function LeadForm({ locale }: { locale: Locale }) {
  const [form, setForm] = useState({
    name: '', company: '', email: '', phone: '', use_case: '', message: '',
  })
  const [loading, setLoading] = useState(false)
  const [success, setSuccess] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const t = useMemo(() => ({
    title: locale === 'ar' ? 'انضم إلينا' : 'Join us',
    desc:  locale === 'ar' ? 'اترك تفاصيلك وسنتواصل معك قريبًا.' : 'Leave your details and we’ll get back to you shortly.',
    send:  locale === 'ar' ? 'إرسال' : 'Send',
    phone: locale === 'ar' ? 'رقم الهاتف (+965...)' : 'Phone (+965...)',
    usecase: locale === 'ar' ? 'مجال الاستخدام' : 'Use case',
    choose: locale === 'ar' ? 'اختر مجال الاستخدام' : 'Select a use case',
    msg:   locale === 'ar' ? 'رسالتك' : 'Your message',
    ok:    locale === 'ar' ? 'تم الاستلام! سنتواصل قريبًا.' : 'Got it! We’ll be in touch shortly.',
  }), [locale])

  function onChange(e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) {
    setForm(prev => ({ ...prev, [e.target.name]: e.target.value }))
  }

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault()
    setLoading(true); setError(null); setSuccess(false)

    // Grab UTMs from the URL
    const params = new URLSearchParams(window.location.search)
    const utm = {
      source: params.get('utm_source') || undefined,
      medium: params.get('utm_medium') || undefined,
      campaign: params.get('utm_campaign') || undefined,
      term: params.get('utm_term') || undefined,
      content: params.get('utm_content') || undefined,
    }

    try {
      const res = await fetch(`${process.env.NEXT_PUBLIC_BACKEND_URL}/api/leads`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        // Backend now normalizes, but we also send the lowercase enum from the select
        body: JSON.stringify({ ...form, locale, utm }),
      })
      const data = await res.json().catch(() => ({}))
      if (!res.ok || data?.ok === false) {
        const msg =
          data?.errors
            ? Object.values(data.errors).flat().join(', ')
            : (data?.message || `HTTP ${res.status}`)
        throw new Error(msg)
      }
      setSuccess(true)
      setForm({ name:'',company:'',email:'',phone:'',use_case:'',message:'' })
    } catch (err: any) {
      setError(err.message || 'Submission failed')
    } finally {
      setLoading(false)
    }
  }

  const waNumber = (process.env.NEXT_PUBLIC_WHATSAPP_NUMBER || '965XXXXXXXX').replace(/\D/g, '')
  const waHref = `https://wa.me/${waNumber}?text=${encodeURIComponent(
    locale==='ar' ? 'أرغب بالانضمام إلى خدمة واتساب بوت' : 'I want to join the WhatsApp bot service'
  )}`

  return (
    <section id="lead" className="bg-slate-50">
      <div className="max-w-3xl mx-auto px-6 py-16">
        <div className="p-6 md:p-8 bg-white rounded-2xl shadow-sm">
          <h2 className="text-2xl font-extrabold mb-1">{t.title}</h2>
          <p className="text-slate-600 mb-6">{t.desc}</p>

          {success && (
            <div className="p-4 mb-6 rounded-lg bg-emerald-50 text-emerald-800 font-semibold">
              {t.ok}
            </div>
          )}
          {error && (
            <div className="p-3 mb-4 rounded bg-amber-50 text-amber-800">{error}</div>
          )}

          <form onSubmit={onSubmit} className="grid md:grid-cols-2 gap-4">
            <input name="name" value={form.name} onChange={onChange} placeholder={locale==='ar'?'الاسم':'Name'} className="border rounded-xl px-4 py-3" />
            <input name="company" value={form.company} onChange={onChange} placeholder={locale==='ar'?'الشركة':'Company'} className="border rounded-xl px-4 py-3" />
            <input name="email" type="email" value={form.email} onChange={onChange} placeholder="Email" className="border rounded-xl px-4 py-3 md:col-span-2" />
            <input name="phone" value={form.phone} onChange={onChange} required placeholder={t.phone} className="border rounded-xl px-4 py-3 md:col-span-2" />

            {/* Use-case dropdown (enum values match backend) */}
            <select name="use_case" value={form.use_case} onChange={onChange} required className="border rounded-xl px-4 py-3 md:col-span-2">
              <option value="">{t.choose}</option>
              {USE_CASE_OPTIONS.map(opt => (
                <option key={opt.value} value={opt.value}>
                  {locale==='ar' ? opt.labelAr : opt.labelEn}
                </option>
              ))}
            </select>

            <textarea name="message" rows={3} value={form.message} onChange={onChange} placeholder={t.msg} className="border rounded-xl px-4 py-3 md:col-span-2" />
            <div className="flex items-center gap-3 md:col-span-2">
              <button disabled={loading} className="px-6 py-3 rounded-xl bg-emerald-600 text-white font-semibold hover:opacity-90 disabled:opacity-60">
                {loading ? '...' : t.send}
              </button>
              <a className="px-6 py-3 rounded-xl border font-semibold" href={waHref}>
                {locale==='ar'?'تواصل واتساب':'Chat on WhatsApp'}
              </a>
            </div>
          </form>
        </div>
      </div>
    </section>
  )
}
