"use client";

import { useState, useMemo } from "react";
import { motion } from "framer-motion";
import { Button } from "@heroui/react";

// --- Type Definitions ---
type Locale = "en" | "ar";
type FormState = {
  name: string;
  company: string;
  email: string;
  phone: string;
  use_case: string;
  message: string;
};

// --- Constants ---
const USE_CASE_OPTIONS = [
  { value: "restaurant", labelEn: "Restaurant", labelAr: "مطاعم" },
  { value: "pharmacy", labelEn: "Pharmacy", labelAr: "صيدلية" },
  { value: "grocery", labelEn: "Grocery / Retail", labelAr: "بقالة / تجزئة" },
  { value: "logistics", labelEn: "Logistics / Services", labelAr: "خدمات / لوجستيات" },
  { value: "other", labelEn: "Other", labelAr: "أخرى" },
];

// --- Helper Components ---
const Alert = ({ type, message }: { type: "success" | "error"; message: string }) => {
  const baseClasses = "p-4 mb-6 rounded-lg font-semibold text-sm flex items-center gap-3";
  const typeClasses = {
    success: "bg-success/10 text-success",
    error: "bg-destructive/10 text-destructive",
  };

  return (
    <div className={`${baseClasses} ${typeClasses[type]}`}>
      <span>{type === "success" ? "✓" : "!"}</span>
      <span>{message}</span>
    </div>
  );
};

const LoadingSpinner = () => (
  <svg
    className="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
    xmlns="http://www.w3.org/2000/svg"
    fill="none"
    viewBox="0 0 24 24"
  >
    <circle
      className="opacity-25"
      cx="12"
      cy="12"
      r="10"
      stroke="currentColor"
      strokeWidth="4"
    ></circle>
    <path
      className="opacity-75"
      fill="currentColor"
      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
    ></path>
  </svg>
);


// --- Main Component ---
export default function LeadForm({ locale }: { locale: Locale }) {
  const [form, setForm] = useState<FormState>({
    name: "", company: "", email: "", phone: "", use_case: "", message: "",
  });
  const [loading, setLoading] = useState(false);
  const [success, setSuccess] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const t = useMemo(
    () => ({
      title: locale === "ar" ? "انضم إلينا" : "Join us",
      desc: locale === "ar" ? "اترك تفاصيلك وسنتواصل معك قريبًا." : "Leave your details and we’ll get back to you shortly.",
      send: locale === "ar" ? "إرسال" : "Send",
      phone: locale === "ar" ? "رقم الهاتف (+965...)" : "Phone (+965...)",
      usecase: locale === "ar" ? "مجال الاستخدام" : "Use case",
      choose: locale === "ar" ? "اختر مجال الاستخدام" : "Select a use case",
      msg: locale === "ar" ? "رسالتك" : "Your message",
      ok: locale === "ar" ? "تم الاستلام! سنتواصل قريبًا." : "Got it! We’ll be in touch shortly.",
      chat: locale === "ar" ? "تواصل واتساب" : "Chat on WhatsApp",
    }),
    [locale]
  );

  function onChange(e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) {
    setForm((prev) => ({ ...prev, [e.target.name]: e.target.value }));
  }

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    setLoading(true);
    setError(null);
    setSuccess(false);

    const params = new URLSearchParams(window.location.search);
    const utm = {
      source: params.get("utm_source") || undefined,
      medium: params.get("utm_medium") || undefined,
      campaign: params.get("utm_campaign") || undefined,
      term: params.get("utm_term") || undefined,
      content: params.get("utm_content") || undefined,
    };

    try {
      const res = await fetch(`${process.env.NEXT_PUBLIC_BACKEND_URL}/api/leads`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ ...form, locale, utm }),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || data?.ok === false) {
        const msg = data?.errors
          ? Object.values(data.errors).flat().join(", ")
          : data?.message || `HTTP ${res.status}`;
        throw new Error(msg);
      }
      setSuccess(true);
      setForm({ name: "", company: "", email: "", phone: "", use_case: "", message: "" });
    } catch (err: any) {
      setError(err.message || "Submission failed");
    } finally {
      setLoading(false);
    }
  }

  const waNumber = (process.env.NEXT_PUBLIC_WHATSAPP_NUMBER || "965XXXXXXXX").replace(/\D/g, "");
  const waHref = `https://wa.me/${waNumber}?text=${encodeURIComponent(
    locale === "ar" ? "أرغب بالانضمام إلى خدمة واتساب بوت" : "I want to join the WhatsApp bot service"
  )}`;

  return (
    <section id="lead" className="bg-secondary section-padding">
      <div className="container-narrow">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, amount: 0.3 }}
          transition={{ duration: 0.6, ease: "easeOut" }}
          className="card p-6 md:p-8"
        >
          <div className="section-heading !text-left max-w-none">
            <h2 className="heading">{t.title}</h2>
            <p className="subheading">{t.desc}</p>
          </div>

          <div className="mt-6">
            {success && <Alert type="success" message={t.ok} />}
            {error && <Alert type="error" message={error} />}
          </div>

          <form onSubmit={onSubmit} className="grid md:grid-cols-2 gap-4">
            <div className="form-group">
              <input name="name" value={form.name} onChange={onChange} placeholder={locale === "ar" ? "الاسم" : "Name"} className="form-input" />
            </div>
            <div className="form-group">
              <input name="company" value={form.company} onChange={onChange} placeholder={locale === "ar" ? "الشركة" : "Company"} className="form-input" />
            </div>
            <div className="form-group md:col-span-2">
              <input name="email" type="email" value={form.email} onChange={onChange} placeholder="Email" className="form-input" />
            </div>
            <div className="form-group md:col-span-2">
              <input name="phone" value={form.phone} onChange={onChange} required placeholder={t.phone} className="form-input" />
            </div>
            <div className="form-group md:col-span-2">
              <select name="use_case" value={form.use_case} onChange={onChange} required className="form-select">
                <option value="">{t.choose}</option>
                {USE_CASE_OPTIONS.map((opt) => (
                  <option key={opt.value} value={opt.value}>
                    {locale === "ar" ? opt.labelAr : opt.labelEn}
                  </option>
                ))}
              </select>
            </div>
            <div className="form-group md:col-span-2">
              <textarea name="message" rows={3} value={form.message} onChange={onChange} placeholder={t.msg} className="form-textarea" />
            </div>
            <div className="flex items-center flex-wrap gap-4 md:col-span-2">
              <Button type="submit" disabled={loading} className="btn btn-lg btn-primary">
                {loading && <LoadingSpinner />}
                {loading ? '...' : t.send}
              </Button>
              <Button as="a" href={waHref} target="_blank" rel="noopener noreferrer" className="btn btn-lg btn-outline">
                {t.chat}
              </Button>
            </div>
          </form>
        </motion.div>
      </div>
    </section>
  );
}
