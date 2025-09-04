import RenderSections from "@/components/sections";
import { fetchPage } from "@/lib/api";
import LeadForm from "@/components/LeadForm";
import Header from "@/components/layout/Header";
import Footer from "@/components/layout/Footer";
import { notFound } from "next/navigation";

type Locale = "en" | "ar";

type PageProps = {
  params: Promise<{ locale: Locale }>;
  // If you ever use searchParams, it’s also a Promise:
  // searchParams?: Promise<Record<string, string | string[] | undefined>>;
};

export default async function Page({ params }: PageProps) {
  const { locale } = await params;        // ⬅️ must await
  const data = await fetchPage("whatsapp-bot", locale);

  if (!data) notFound();

  return (
    <>
      <Header locale={locale} />
      <main>
        <RenderSections sections={data?.sections ?? []} />
        <LeadForm locale={locale} />
      </main>
      <Footer locale={locale} />
    </>
  );
}
