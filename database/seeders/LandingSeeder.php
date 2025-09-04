<?php

namespace Database\Seeders;

use App\Models\LandingPage;
use Illuminate\Database\Seeder;

class LandingSeeder extends Seeder
{
    public function run(): void
    {
        $base = rtrim(config('app.url'), '/'); // e.g. https://zad-hub.com
        $wa = preg_replace('/\D+/', '', env('WHATSAPP_DEMO_NUMBER', '96555907578')); // digits only

        // ----- Shared image placeholders (adjust or upload in Filament) -----
        $heroImg = "{$base}/storage/landing/hero/sample-hero.webp";
        $logo1 = "{$base}/storage/landing/logos/logo1.webp";
        $logo2 = "{$base}/storage/landing/logos/logo2.webp";
        $logo3 = "{$base}/storage/landing/logos/logo3.webp";
        $logo4 = "{$base}/storage/landing/logos/logo4.webp";
        $logo5 = "{$base}/storage/landing/logos/logo5.webp";
        $logo6 = "{$base}/storage/landing/logos/logo6.webp";
        $slice1 = "{$base}/storage/landing/slices/restaurant.webp";
        $slice2 = "{$base}/storage/landing/slices/pharmacy.webp";
        $slice3 = "{$base}/storage/landing/slices/grocery.webp";
        $slice4 = "{$base}/storage/landing/slices/logistics.webp";

        // =====================================================================
        // EN sections
        // =====================================================================
        $sectionsEn = [
            [
                'type' => 'hero',
                'data' => [
                    'eyebrow' => 'Kuwait • WhatsApp',
                    'heading' => 'Turn WhatsApp into your best sales channel',
                    'subheading' => 'Flows, catalogs, payments—ready in days.',
                    'primary' => ['label' => 'Get started',   'href' => '#lead'],
                    'secondary' => [
                        'label' => 'WhatsApp demo',
                        'href' => "https://wa.me/{$wa}?text=".rawurlencode('Hi, I want a WhatsApp bot demo'),
                    ],
                    'image' => $heroImg,
                    'dark' => true,
                ],
            ],
            [
                'type' => 'logos',
                'data' => [
                    'items' => [
                        ['logo' => $logo1, 'alt' => 'Client 1'],
                        ['logo' => $logo2, 'alt' => 'Client 2'],
                        ['logo' => $logo3, 'alt' => 'Client 3'],
                        ['logo' => $logo4, 'alt' => 'Client 4'],
                        ['logo' => $logo5, 'alt' => 'Client 5'],
                        ['logo' => $logo6, 'alt' => 'Client 6'],
                    ],
                ],
            ],
            [
                'type' => 'why_us',
                'data' => [
                    'items' => [
                        ['title' => 'Arabic + English', 'body' => 'RTL done right for Kuwait.',            'icon' => '🇰🇼'],
                        ['title' => 'Payments built-in', 'body' => 'MyFatoorah links and receipts.',        'icon' => '💳'],
                        ['title' => 'Fast setup',        'body' => 'Approved templates & smart flows.',      'icon' => '⚙️'],
                    ],
                ],
            ],
            [
                'type' => 'features_grid',
                'data' => [
                    'features' => [
                        ['title' => 'Flows & Templates', 'body' => 'Handle approvals and the 24h window easily.', 'icon' => '📜'],
                        ['title' => 'Catalogs',          'body' => 'Show products and take orders in chat.',      'icon' => '🛒'],
                        ['title' => 'Broadcasts',        'body' => 'Send targeted messages with consent.',        'icon' => '📢'],
                        ['title' => 'Analytics',         'body' => 'See orders, response times, and CSAT.',       'icon' => '📈'],
                    ],
                ],
            ],
            [
                'type' => 'industry_slices',
                'data' => [
                    'slices' => [
                        [
                            'kicker' => 'Restaurants',
                            'headline' => 'Take WhatsApp orders with payment links',
                            'copy' => 'Automate order taking, confirmations, and delivery updates.',
                            'image' => $slice1,
                            'reverse' => false,
                        ],
                        [
                            'kicker' => 'Pharmacies',
                            'headline' => 'Refills and delivery via WhatsApp',
                            'copy' => 'Collect prescriptions and schedule deliveries in minutes.',
                            'image' => $slice2,
                            'reverse' => true,
                        ],
                        [
                            'kicker' => 'Grocery / Retail',
                            'headline' => 'Catalog + cart inside chat',
                            'copy' => 'Share product lists, collect addresses, and accept payments.',
                            'image' => $slice3,
                            'reverse' => false,
                        ],
                        [
                            'kicker' => 'Logistics / Services',
                            'headline' => 'Bookings, reminders, and status updates',
                            'copy' => 'Reduce missed appointments and manual follow-ups.',
                            'image' => $slice4,
                            'reverse' => true,
                        ],
                    ],
                ],
            ],
            [
                'type' => 'pricing',
                'data' => [
                    'plans' => [
                        [
                            'name' => 'Starter',
                            'price_text' => '29 KWD/mo',
                            'summary' => 'Great for first deployments.',
                            'bullets' => [
                                ['text' => 'Up to 2 flows'],
                                ['text' => 'Basic broadcasts'],
                                ['text' => 'Email support'],
                            ],
                            'cta' => ['label' => 'Get started', 'href' => '#lead'],
                            'featured' => false,
                        ],
                        [
                            'name' => 'Business',
                            'price_text' => '79 KWD/mo',
                            'summary' => 'More flows and full analytics.',
                            'bullets' => [
                                ['text' => 'Up to 6 flows'],
                                ['text' => 'Advanced broadcasts'],
                                ['text' => 'Analytics dashboard'],
                            ],
                            'cta' => ['label' => 'Talk to sales', 'href' => '#lead'],
                            'featured' => true,
                        ],
                        [
                            'name' => 'Enterprise',
                            'price_text' => 'Custom',
                            'summary' => 'SLA, SSO, and custom integrations.',
                            'bullets' => [
                                ['text' => 'Unlimited flows'],
                                ['text' => 'Priority support'],
                                ['text' => 'Custom integrations'],
                            ],
                            'cta' => ['label' => 'Contact us', 'href' => '#lead'],
                            'featured' => false,
                        ],
                    ],
                    'note' => 'Prices exclude WhatsApp BSP fees and payment gateway charges.',
                ],
            ],
            [
                'type' => 'faq',
                'data' => [
                    'items' => [
                        ['q' => 'How long does setup take?', 'a' => 'Most businesses go live in 3–7 days.'],
                        ['q' => 'Do you support Arabic?',     'a' => 'Yes—full RTL and Arabic/English templates.'],
                        ['q' => 'Do you support payments?',   'a' => 'We support MyFatoorah and payment links.'],
                        ['q' => 'What about approvals?',      'a' => 'We help you prepare and approve WhatsApp templates.'],
                    ],
                ],
            ],
            [
                'type' => 'cta',
                'data' => [
                    'heading' => 'Ready to launch on WhatsApp?',
                    'subheading' => 'Tell us about your use case and we’ll get you a demo.',
                    'cta' => ['label' => 'Get started', 'href' => '#lead'],
                ],
            ],
        ];

        // =====================================================================
        // AR sections
        // =====================================================================
        $sectionsAr = [
            [
                'type' => 'hero',
                'data' => [
                    'eyebrow' => 'الكويت • واتساب',
                    'heading' => 'حوّل واتساب إلى أقوى قناة مبيعات لديك',
                    'subheading' => 'تدفقات، كتالوجات، مدفوعات — جاهزة خلال أيام.',
                    'primary' => ['label' => 'ابدأ الآن', 'href' => '#lead'],
                    'secondary' => [
                        'label' => 'تجربة واتساب',
                        'href' => "https://wa.me/{$wa}?text=".rawurlencode('أرغب بتجربة واتساب بوت'),
                    ],
                    'image' => $heroImg,
                    'dark' => true,
                ],
            ],
            [
                'type' => 'logos',
                'data' => [
                    'items' => [
                        ['logo' => $logo1, 'alt' => 'عميل 1'],
                        ['logo' => $logo2, 'alt' => 'عميل 2'],
                        ['logo' => $logo3, 'alt' => 'عميل 3'],
                        ['logo' => $logo4, 'alt' => 'عميل 4'],
                        ['logo' => $logo5, 'alt' => 'عميل 5'],
                        ['logo' => $logo6, 'alt' => 'عميل 6'],
                    ],
                ],
            ],
            [
                'type' => 'why_us',
                'data' => [
                    'items' => [
                        ['title' => 'عربي + إنجليزي', 'body' => 'دعم كامل للاتجاه من اليمين لليسار.',         'icon' => '🇰🇼'],
                        ['title' => 'مدفوعات مدمجة',  'body' => 'روابط دفع MyFatoorah وإيصالات.',            'icon' => '💳'],
                        ['title' => 'انطلاق سريع',     'body' => 'قوالب معتمدة وتدفقات ذكية.',                'icon' => '⚙️'],
                    ],
                ],
            ],
            [
                'type' => 'features_grid',
                'data' => [
                    'features' => [
                        ['title' => 'التدفقات والقوالب', 'body' => 'إدارة الموافقات ونوافذ الـ24 ساعة بسهولة.', 'icon' => '📜'],
                        ['title' => 'الكتالوجات',         'body' => 'اعرض المنتجات واستقبل الطلبات داخل المحادثة.', 'icon' => '🛒'],
                        ['title' => 'الرسائل الجماعية',   'body' => 'إرسال رسائل مستهدفة بموافقة المستخدم.',       'icon' => '📢'],
                        ['title' => 'التحليلات',          'body' => 'اطّلع على الطلبات وأوقات الرد ورضا العملاء.',  'icon' => '📈'],
                    ],
                ],
            ],
            [
                'type' => 'industry_slices',
                'data' => [
                    'slices' => [
                        [
                            'kicker' => 'مطاعم',
                            'headline' => 'استقبل الطلبات وروابط الدفع عبر واتساب',
                            'copy' => 'أتمتة استقبال الطلبات والتأكيدات وتحديثات التوصيل.',
                            'image' => $slice1,
                            'reverse' => false,
                        ],
                        [
                            'kicker' => 'صيدليات',
                            'headline' => 'تجديد الوصفات والتوصيل عبر واتساب',
                            'copy' => 'استلام الوصفات وجدولة التوصيل خلال دقائق.',
                            'image' => $slice2,
                            'reverse' => true,
                        ],
                        [
                            'kicker' => 'بقالة / تجزئة',
                            'headline' => 'كتالوج وسلة داخل المحادثة',
                            'copy' => 'مشاركة القوائم، جمع العناوين، واستلام المدفوعات.',
                            'image' => $slice3,
                            'reverse' => false,
                        ],
                        [
                            'kicker' => 'خدمات / لوجستيات',
                            'headline' => 'حجوزات وتذكيرات وحالة الطلب',
                            'copy' => 'تقليل المواعيد الفائتة والمتابعة اليدوية.',
                            'image' => $slice4,
                            'reverse' => true,
                        ],
                    ],
                ],
            ],
            [
                'type' => 'pricing',
                'data' => [
                    'plans' => [
                        [
                            'name' => 'بداية',
                            'price_text' => '29 د.ك / شهر',
                            'summary' => 'مناسب للانطلاقة الأولى.',
                            'bullets' => [
                                ['text' => 'حتى تدفقين'],
                                ['text' => 'رسائل جماعية أساسية'],
                                ['text' => 'دعم بالبريد'],
                            ],
                            'cta' => ['label' => 'ابدأ الآن', 'href' => '#lead'],
                            'featured' => false,
                        ],
                        [
                            'name' => 'أعمال',
                            'price_text' => '79 د.ك / شهر',
                            'summary' => 'مزيد من التدفقات وتحليلات كاملة.',
                            'bullets' => [
                                ['text' => 'حتى 6 تدفقات'],
                                ['text' => 'رسائل جماعية متقدمة'],
                                ['text' => 'لوحة تحكم تحليلات'],
                            ],
                            'cta' => ['label' => 'تحدث مع المبيعات', 'href' => '#lead'],
                            'featured' => true,
                        ],
                        [
                            'name' => 'مؤسسات',
                            'price_text' => 'اتصل بنا',
                            'summary' => 'اتفاقيات مستوى الخدمة وتكاملات مخصصة.',
                            'bullets' => [
                                ['text' => 'تدفقات غير محدودة'],
                                ['text' => 'دعم أولوية'],
                                ['text' => 'تكاملات خاصة'],
                            ],
                            'cta' => ['label' => 'تواصل معنا', 'href' => '#lead'],
                            'featured' => false,
                        ],
                    ],
                    'note' => 'الأسعار لا تشمل رسوم مزود واتساب ولا رسوم بوابة الدفع.',
                ],
            ],
            [
                'type' => 'faq',
                'data' => [
                    'items' => [
                        ['q' => 'كم يستغرق الإعداد؟',      'a' => 'معظم الشركات تنطلق خلال 3–7 أيام.'],
                        ['q' => 'هل تدعمون العربية؟',       'a' => 'نعم — دعم كامل للواجهة العربية واتجاه RTL.'],
                        ['q' => 'هل توجد مدفوعات؟',         'a' => 'ندعم MyFatoorah وروابط الدفع.'],
                        ['q' => 'ماذا عن الموافقات؟',        'a' => 'نساعدك في إعداد واعتماد قوالب واتساب.'],
                    ],
                ],
            ],
            [
                'type' => 'cta',
                'data' => [
                    'heading' => 'جاهز للانطلاق على واتساب؟',
                    'subheading' => 'اذكر لنا حالتك وسنرتب لك تجربة سريعة.',
                    'cta' => ['label' => 'ابدأ الآن', 'href' => '#lead'],
                ],
            ],
        ];

        // =====================================================================
        // Write EN
        // =====================================================================
        LandingPage::updateOrCreate(
            ['slug' => 'whatsapp-bot', 'locale' => 'en'],
            [
                'title' => 'Automate WhatsApp for Your Business',
                'meta_title' => 'WhatsApp Bot – Kuwait',
                'meta_description' => 'AR/EN flows, payments, templates, analytics.',
                'sections' => $sectionsEn,
                'is_published' => true,
                'published_at' => now(),
                'version' => 1,
            ]
        );

        // =====================================================================
        // Write AR
        // =====================================================================
        LandingPage::updateOrCreate(
            ['slug' => 'whatsapp-bot', 'locale' => 'ar'],
            [
                'title' => 'أتمتة واتساب للأعمال',
                'meta_title' => 'واتساب بوت – الكويت',
                'meta_description' => 'عربي/إنجليزي، مدفوعات، قوالب، تحليلات.',
                'sections' => $sectionsAr,
                'is_published' => true,
                'published_at' => now(),
                'version' => 1,
            ]
        );
    }
}
