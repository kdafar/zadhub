<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /** Keep only keys that exist as columns on a table */
    private function onlyExistingColumns(string $table, array $row): array
    {
        static $columnsCache = [];
        if (! isset($columnsCache[$table])) {
            $columnsCache[$table] = Schema::getColumnListing($table);
        }
        $allowed = $columnsCache[$table];

        // convert arrays to JSON for json columns (DB accepts array->string too, but be explicit)
        foreach ($row as $k => $v) {
            if (is_array($v)) {
                $row[$k] = json_encode($v, JSON_UNESCAPED_UNICODE);
            }
        }

        return array_intersect_key($row, array_flip($allowed));
    }

    public function run(): void
    {
        DB::transaction(function () {
            $now = Carbon::now();

            /**
             * 1) SERVICE TYPES
             * Your table is now `service_types`.
             * We’ll also write optional columns only if they exist (code, description, default_locale, is_active, meta).
             */
            $serviceTypes = [
                [
                    'code' => 'restaurant',         // only if column exists
                    'slug' => Str::slug('restaurant'),
                    'name_en' => 'Restaurant',
                    'name_ar' => 'مطعم',
                    'description' => 'Food ordering, delivery & pickup flows',
                    'default_locale' => 'en',
                    'is_active' => true,
                    'meta' => ['supports' => ['address', 'menu', 'checkout']],
                    'message_templates' => [
                        'en' => [
                            'fallback' => 'Sorry, I didn\'t understand that. Please use one of the keywords to get started.',
                            'flow_completed' => 'Thank you! Your request has been received.',
                        ],
                        'ar' => [
                            'fallback' => 'عذراً، لم أفهم ذلك. الرجاء استخدام إحدى الكلمات الرئيسية للبدء.',
                            'flow_completed' => 'شكراً لك! تم استلام طلبك.',
                        ],
                    ],
                ],
                [
                    'code' => 'telecom',
                    'slug' => Str::slug('telecom'),
                    'name_en' => 'Telecom',
                    'name_ar' => 'اتصالات',
                    'description' => 'Plans, balance, recharge, support',
                    'default_locale' => 'en',
                    'is_active' => true,
                    'meta' => ['supports' => ['balance', 'plans', 'payments']],
                    'message_templates' => [],
                ],
                [
                    'code' => 'hospital',
                    'slug' => Str::slug('hospital'),
                    'name_en' => 'Hospital',
                    'name_ar' => 'مستشفى',
                    'description' => 'Doctors, appointments, prescriptions',
                    'default_locale' => 'en',
                    'is_active' => true,
                    'meta' => ['supports' => ['doctors', 'appointments', 'pharmacy']],
                    'message_templates' => [],
                ],
            ];

            foreach ($serviceTypes as $svc) {
                // Upsert by slug (always exists on your table)
                $payload = $this->onlyExistingColumns('service_types', $svc) + [
                    'updated_at' => $now,
                    'created_at' => $now,
                ];
                DB::table('service_types')->updateOrInsert(
                    ['slug' => $payload['slug']],
                    $payload
                );
            }

            $svcRestaurant = DB::table('service_types')->where('slug', 'restaurant')->first();
            $svcTelecom = DB::table('service_types')->where('slug', 'telecom')->first();
            $svcHospital = DB::table('service_types')->where('slug', 'hospital')->first();

            /**
             * 2) PROVIDERS
             * Your table has: service_type_id, name, slug, status, api_base_url, auth_type, is_sandbox, locale_defaults, feature_flags, timestamps, deleted_at
             * (No is_active / callback_url / contact_* / timezone / meta)
             */
            $providers = [
                [
                    'service_type_id' => $svcRestaurant->id,
                    'name' => 'BannerKW Eats',
                    'slug' => 'bannerkw-eats',
                    'status' => 'active',
                    'api_base_url' => 'https://api.bannerkw-eats.example.com',
                    'auth_type' => 'none',
                    'is_sandbox' => 0,
                    'locale_defaults' => ['en' => ['currency' => 'KWD']],
                    'feature_flags' => ['catalog' => true],
                ],
                [
                    'service_type_id' => $svcTelecom->id,
                    'name' => 'Zad Telecom',
                    'slug' => 'zad-telecom',
                    'status' => 'active',
                    'api_base_url' => 'https://api.zad-telecom.example.com',
                    'auth_type' => 'none',
                    'is_sandbox' => 0,
                    'locale_defaults' => ['en' => ['currency' => 'KWD']],
                    'feature_flags' => ['esim' => true],
                ],
                [
                    'service_type_id' => $svcHospital->id,
                    'name' => 'CarePlus Hospital',
                    'slug' => 'careplus-hospital',
                    'status' => 'active',
                    'api_base_url' => 'https://api.careplus-hospital.example.com',
                    'auth_type' => 'none',
                    'is_sandbox' => 0,
                    'locale_defaults' => ['en' => ['currency' => 'KWD']],
                    'feature_flags' => ['telemed' => true],
                ],
            ];

            foreach ($providers as $p) {
                $payload = $this->onlyExistingColumns('providers', $p) + [
                    'updated_at' => $now,
                    'created_at' => $now,
                ];
                DB::table('providers')->updateOrInsert(
                    ['slug' => $payload['slug']],
                    $payload
                );
            }

            $provRestaurant = DB::table('providers')->where('slug', 'bannerkw-eats')->first();
            $provTelecom = DB::table('providers')->where('slug', 'zad-telecom')->first();
            $provHospital = DB::table('providers')->where('slug', 'careplus-hospital')->first();

            /**
             * 3) PROVIDER CREDENTIALS
             * Table: id, provider_id, key_name, secret_encrypted, meta(json), timestamps
             */
            $creds = [
                ['provider_id' => $provRestaurant->id, 'key_name' => 'api_key',    'secret' => 'rest_abc123', 'meta' => ['is_secret' => true]],
                ['provider_id' => $provRestaurant->id, 'key_name' => 'catalog_id', 'secret' => 'cat_123',     'meta' => ['is_secret' => false]],
                ['provider_id' => $provTelecom->id,    'key_name' => 'api_key',    'secret' => 'tel_abc123',  'meta' => ['is_secret' => true]],
                ['provider_id' => $provHospital->id,   'key_name' => 'api_key',    'secret' => 'hosp_abc123', 'meta' => ['is_secret' => true]],
            ];
            foreach ($creds as $c) {
                $row = [
                    'provider_id' => $c['provider_id'],
                    'key_name' => $c['key_name'],
                    'secret_encrypted' => Crypt::encryptString($c['secret']),
                    'meta' => $c['meta'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $payload = $this->onlyExistingColumns('provider_credentials', $row);
                DB::table('provider_credentials')->updateOrInsert(
                    ['provider_id' => $c['provider_id'], 'key_name' => $c['key_name']],
                    $payload
                );
            }

            /**
             * 4) FLOW_TEMPLATES (id, service_type_id, slug, name, description, latest_version_id, timestamps)
             */
            $tpls = [
                ['service_type_id' => $svcRestaurant->id, 'slug' => Str::slug('Restaurant Main Flow'), 'name' => 'Restaurant Main Flow', 'description' => 'Base order flow (address → menu → cart → checkout)'],
                ['service_type_id' => $svcTelecom->id,    'slug' => Str::slug('Telecom Main Flow'),    'name' => 'Telecom Main Flow',    'description' => 'Balance, plans, recharge flow'],
                ['service_type_id' => $svcHospital->id,   'slug' => Str::slug('Hospital Main Flow'),   'name' => 'Hospital Main Flow',   'description' => 'Appointments & doctors flow'],
            ];
            foreach ($tpls as $t) {
                $payload = $this->onlyExistingColumns('flow_templates', $t) + ['updated_at' => $now, 'created_at' => $now];
                DB::table('flow_templates')->updateOrInsert(['slug' => $payload['slug']], $payload);
            }

            $ftRest = DB::table('flow_templates')->where('slug', Str::slug('Restaurant Main Flow'))->first();
            $ftTel = DB::table('flow_templates')->where('slug', Str::slug('Telecom Main Flow'))->first();
            $ftHosp = DB::table('flow_templates')->where('slug', Str::slug('Hospital Main Flow'))->first();

            /**
             * 5) FLOW_VERSIONS (id, flow_template_id, version, is_stable, schema_json, components_json, timestamps)
             */
            $versions = [
                [
                    'flow_template_id' => $ftRest->id,
                    'version' => 1,
                    'is_stable' => 1,
                    'schema_json' => ['v' => '1.0', 'entry' => 'ADDRESS_SAVED'],
                    'components_json' => ['screens' => ['ADDRESS_SAVED', 'SELECT_CUISINE', 'SELECT_RESTAURANT', 'SELECT_CATEGORY', 'SELECT_ITEM', 'ITEM_QTY', 'CART_SCREEN', 'CHECKOUT_START', 'ADDRESS_BLOCK', 'CONFIRMATION_SCREEN']],
                ],
                [
                    'flow_template_id' => $ftTel->id,
                    'version' => 1,
                    'is_stable' => 1,
                    'schema_json' => ['v' => '1.0', 'entry' => 'TEL_HOME'],
                    'components_json' => ['screens' => ['TEL_HOME', 'BALANCE', 'PLANS', 'RECHARGE', 'PAYMENT']],
                ],
                [
                    'flow_template_id' => $ftHosp->id,
                    'version' => 1,
                    'is_stable' => 1,
                    'schema_json' => ['v' => '1.0', 'entry' => 'HOSP_HOME'],
                    'components_json' => ['screens' => ['HOSP_HOME', 'DOCTORS', 'SLOTS', 'APPOINTMENT', 'CONFIRM']],
                ],
            ];

            foreach ($versions as $v) {
                $payload = $this->onlyExistingColumns('flow_versions', $v) + ['updated_at' => $now, 'created_at' => $now];
                $existing = DB::table('flow_versions')
                    ->where('flow_template_id', $v['flow_template_id'])
                    ->where('version', $v['version'])
                    ->first();

                if ($existing) {
                    DB::table('flow_versions')->where('id', $existing->id)->update($payload);
                    $versionId = $existing->id;
                } else {
                    $versionId = DB::table('flow_versions')->insertGetId($payload);
                }

                // update latest_version_id on template if exists
                if (Schema::hasColumn('flow_templates', 'latest_version_id')) {
                    DB::table('flow_templates')->where('id', $v['flow_template_id'])->update([
                        'latest_version_id' => $versionId,
                        'updated_at' => $now,
                    ]);
                }
            }

            $fvRest = DB::table('flow_versions')->where('flow_template_id', $ftRest->id)->where('version', 1)->first();
            $fvTel = DB::table('flow_versions')->where('flow_template_id', $ftTel->id)->where('version', 1)->first();
            $fvHosp = DB::table('flow_versions')->where('flow_template_id', $ftHosp->id)->where('version', 1)->first();

            /**
             * 6) PROVIDER_FLOW_PINS (provider_id, flow_template_id, pinned_version_id, timestamps)
             */
            $pins = [
                ['provider_id' => $provRestaurant->id, 'flow_template_id' => $ftRest->id, 'pinned_version_id' => $fvRest->id],
                ['provider_id' => $provTelecom->id,    'flow_template_id' => $ftTel->id,  'pinned_version_id' => $fvTel->id],
                ['provider_id' => $provHospital->id,   'flow_template_id' => $ftHosp->id, 'pinned_version_id' => $fvHosp->id],
            ];
            foreach ($pins as $pin) {
                $payload = $this->onlyExistingColumns('provider_flow_pins', $pin) + ['updated_at' => $now, 'created_at' => $now];
                DB::table('provider_flow_pins')->updateOrInsert(
                    ['provider_id' => $pin['provider_id'], 'flow_template_id' => $pin['flow_template_id']],
                    $payload
                );
            }

            /**
             * 7) PROVIDER_FLOW_OVERRIDES (provider_id, flow_version_id, overrides_json, timestamps)
             */
            $override = [
                'provider_id' => $provRestaurant->id,
                'flow_version_id' => $fvRest->id,
                'overrides_json' => ['branding' => ['primaryColor' => '#e74c3c']],
            ];
            $payload = $this->onlyExistingColumns('provider_flow_overrides', $override) + ['updated_at' => $now, 'created_at' => $now];
            DB::table('provider_flow_overrides')->updateOrInsert(
                ['provider_id' => $override['provider_id'], 'flow_version_id' => $override['flow_version_id']],
                $payload
            );

            /**
             * 8) SERVICE_KEYWORDS
             * Your error shows 'weight' column does NOT exist. We’ll include it only if present.
             * Common columns: service_type_id, keyword, locale, (is_active?), timestamps
             */
            $keywords = [
                ['service_type_id' => $svcRestaurant->id, 'keyword' => 'menu',     'locale' => 'en', 'is_active' => true, 'weight' => 10],
                ['service_type_id' => $svcRestaurant->id, 'keyword' => 'order',    'locale' => 'en', 'is_active' => true, 'weight' => 9],
                ['service_type_id' => $svcTelecom->id,    'keyword' => 'balance',  'locale' => 'en', 'is_active' => true, 'weight' => 10],
                ['service_type_id' => $svcTelecom->id,    'keyword' => 'plans',    'locale' => 'en', 'is_active' => true, 'weight' => 9],
                ['service_type_id' => $svcHospital->id,   'keyword' => 'doctor',   'locale' => 'en', 'is_active' => true, 'weight' => 10],
                ['service_type_id' => $svcHospital->id,   'keyword' => 'clinic',   'locale' => 'en', 'is_active' => true, 'weight' => 9],
            ];
            foreach ($keywords as $kw) {
                $payload = $this->onlyExistingColumns('service_keywords', $kw) + ['updated_at' => $now, 'created_at' => $now];
                DB::table('service_keywords')->updateOrInsert(
                    ['service_type_id' => $kw['service_type_id'], 'keyword' => $kw['keyword'], 'locale' => $kw['locale']],
                    $payload
                );
            }
            // ---------------------------
            // 9) Provider Routing Rules (schema-aware)
            // ---------------------------
            // ---------------------------
            // 8) Provider Routing Rules  (fits your actual schema)
            // ---------------------------
            $rules = [
                [
                    'provider_id' => $provRestaurant->id,
                    'rule_type' => 'custom', // store keyword routing in rule_config
                    'rule_config' => [
                        'mode' => 'keyword',
                        'pattern' => 'menu|order',
                        'route' => [
                            'flow_template_id' => $ftRest->id,
                            'flow_version_id' => $fvRest->id,
                        ],
                    ],
                ],
                [
                    'provider_id' => $provTelecom->id,
                    'rule_type' => 'custom',
                    'rule_config' => [
                        'mode' => 'keyword',
                        'pattern' => 'balance|plans|recharge',
                        'route' => [
                            'flow_template_id' => $ftTel->id,
                            'flow_version_id' => $fvTel->id,
                        ],
                    ],
                ],
                [
                    'provider_id' => $provHospital->id,
                    'rule_type' => 'custom',
                    'rule_config' => [
                        'mode' => 'keyword',
                        'pattern' => 'doctor|appointment',
                        'route' => [
                            'flow_template_id' => $ftHosp->id,
                            'flow_version_id' => $fvHosp->id,
                        ],
                    ],
                ],
            ];

            // upsert by (provider_id, rule_type)
            foreach ($rules as $r) {
                DB::table('provider_routing_rules')->updateOrInsert(
                    [
                        'provider_id' => $r['provider_id'],
                        'rule_type' => $r['rule_type'],
                    ],
                    [
                        'rule_config' => json_encode($r['rule_config']),
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }

            /**
             * 10) WHATSAPP SESSION
             */
            $session = [
                'provider_id' => $provRestaurant->id,
                'phone' => '+96555000000',
                'status' => 'active',
                'locale' => 'en',
                'flow_token' => (string) Str::uuid(),
                'last_interacted_at' => $now,
                'meta' => ['entry' => 'seed'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $sessionId = DB::table('whatsapp_sessions')->insertGetId($this->onlyExistingColumns('whatsapp_sessions', $session));

            /**
             * 11) SESSION STATE
             */
            $stateRow = [
                'session_id' => $sessionId,
                'screen' => 'address',
                'state_json' => ['state_id' => 1, 'city_id' => 1, 'block_id' => 1, 'street' => 'Block 1, Street 10'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
            DB::table('session_states')->updateOrInsert(
                ['session_id' => $sessionId, 'screen' => 'address'],
                $this->onlyExistingColumns('session_states', $stateRow)
            );

            /**
             * 12) CART + ITEMS
             */
            $cart = [
                'provider_id' => $provRestaurant->id,
                'session_id' => $sessionId,
                'status' => 'open',
                'currency' => 'KWD',
                'subtotal' => 3.500,
                'discount' => 0.000,
                'total' => 3.500,
                'meta' => ['notes' => 'seed cart'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $cartId = DB::table('carts')->insertGetId($this->onlyExistingColumns('carts', $cart));

            $item = [
                'cart_id' => $cartId,
                'item_ref' => 'item_101',
                'title' => 'Chicken Shawarma',
                'price' => 1.750,
                'quantity' => 2,
                'variations_json' => [['id' => '1-3', 'title' => 'Garlic Sauce', 'price' => 0.250]],
                'created_at' => $now,
                'updated_at' => $now,
            ];
            DB::table('cart_items')->insert($this->onlyExistingColumns('cart_items', $item));

            /**
             * 13) ORDER
             */
            $order = [
                'provider_id' => $provRestaurant->id,
                'session_id' => $sessionId,
                'service_type_id' => $svcRestaurant->id,
                'cart_id' => $cartId,
                'external_id' => 'ORD-TEST-1001',
                'status' => 'pending',
                'currency' => 'KWD',
                'subtotal' => 3.500,
                'discount' => 0.000,
                'delivery_fee' => 0.500,
                'total' => 4.000,
                'address_json' => ['street' => 'Block 1, Street 10', 'house_no' => '12'],
                'meta' => ['source' => 'seed'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $orderId = DB::table('orders')->insertGetId($this->onlyExistingColumns('orders', $order));

            /**
             * 14) PAYMENT LINK
             */
            $pay = [
                'order_id' => $orderId,
                'provider_id' => $provRestaurant->id,
                'external_url' => 'https://pay.example.com/invoice/INV-SEED-1001',
                'status' => 'new',
                'amount' => 4.000,
                'currency' => 'KWD',
                'expires_at' => $now->copy()->addDay(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
            DB::table('payment_links')->updateOrInsert(
                ['order_id' => $orderId],
                $this->onlyExistingColumns('payment_links', $pay)
            );

            /**
             * 15) PROVIDER WEBHOOK LOGS
             */
            $wh = [
                'provider_id' => $provRestaurant->id,
                'event' => 'outgoing',
                'url' => 'https://hub.example.com/webhooks/provider/bannerkw-eats',
                'status_code' => 200,
                'headers_json' => ['Content-Type' => 'application/json'],
                'payload' => ['event' => 'order.created', 'order_id' => $orderId],
                'error' => null,
                'took_ms' => 123,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            DB::table('provider_webhook_logs')->insert($this->onlyExistingColumns('provider_webhook_logs', $wh));

            /**
             * 16) PROVIDER HEALTH CHECKS
             */
            $hc = [
                'provider_id' => $provRestaurant->id,
                'status' => 'up',
                'checked_at' => $now,
                'details_json' => ['uptime' => '99.99%'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
            DB::table('provider_health_checks')->insert($this->onlyExistingColumns('provider_health_checks', $hc));

            /**
             * 17) PROVIDER RATE LIMITS
             */
            $rl = [
                'provider_id' => $provRestaurant->id,
                'window_seconds' => 60,
                'max_calls' => 120,
                'current_count' => 0,
                'reset_at' => $now->copy()->addMinute(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
            DB::table('provider_rate_limits')->updateOrInsert(
                ['provider_id' => $provRestaurant->id, 'max_calls' => 120],
                $this->onlyExistingColumns('provider_rate_limits', $rl)
            );

            /**
             * 18) ADAPTER API LOGS
             */
            $apiLog = [
                'provider_id' => $provRestaurant->id,
                'adapter' => 'WhatsApp',
                'method' => 'POST',
                'endpoint' => 'https://graph.facebook.com/v20.0/PHONE_ID/messages',
                'status_code' => 200,
                'req_headers' => ['Authorization' => 'Bearer ***'],
                'req_body' => ['messaging_product' => 'whatsapp', 'to' => '+96555000000'],
                'res_headers' => ['Content-Type' => 'application/json'],
                'res_body' => ['messages' => [['id' => 'wamid.SOME-ID']]],
                'took_ms' => 220,
                'error' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            DB::table('adapter_api_logs')->insert($this->onlyExistingColumns('adapter_api_logs', $apiLog));

            /**
             * 21) ANALYTICS EVENTS
             */
            $ae = [
                'provider_id' => $provRestaurant->id,
                'whatsapp_session_id' => $sessionId,
                'event' => 'flow_started',
                'properties_json' => ['flow' => 'Restaurant Main Flow', 'locale' => 'en'],
                'occurred_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            DB::table('analytics_events')->insert($this->onlyExistingColumns('analytics_events', $ae));
        });
    }
}
