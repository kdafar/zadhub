<x-filament-panels::page.simple>
    @if(method_exists($this, 'getHeaderWidgets'))
        <x-filament-widgets::widgets
            :widgets="$this->getHeaderWidgets()"
            :columns="$this->getHeaderWidgetsColumns()"
            class="fi-page-header-widgets"
        />
    @endif

    <style>
        .custom-prose h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
        .custom-prose h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
        }
        .custom-prose p, .custom-prose ul, .custom-prose ol {
            line-height: 1.7;
        }
        .custom-prose ul, .custom-prose ol {
            padding-left: 1.5rem;
        }
        .custom-prose li {
            margin-bottom: 0.5rem;
        }
        .custom-prose code {
            background-color: var(--gray-100);
            color: var(--danger-600);
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.9em;
        }
        .dark .custom-prose code {
            background-color: var(--gray-800);
            color: var(--danger-400);
        }
        .quick-link-card {
            transition: all 0.2s ease-in-out;
        }
        .quick-link-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .dark .quick-link-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
    </style>

    <div class="custom-prose text-gray-600 dark:text-gray-400">
        <x-filament::section>
            <div class="space-y-4">
                <h2 class="!mt-0">Welcome to the WhatsApp Platform Hub!</h2>
                <p class="text-lg">
                    This is your command center for creating and managing powerful, automated WhatsApp conversations. Think of this platform as a factory for building custom chatbots for different types of businesses.
                </p>

                <h3>What is this Platform?</h3>
                <p>
                    Imagine you want to create a WhatsApp bot for a restaurant to take orders, and another for a clinic to book appointments. Instead of building each one from scratch, this platform lets you create a reusable <strong>"Blueprint"</strong> for each business type. Then, you can launch new bots for specific businesses quickly and easily.
                </p>

                <h3>The Building Blocks</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-2">
                        <x-filament::icon icon="heroicon-o-clipboard-document-list" class="w-8 h-8 text-primary-500" />
                        <h4>Service Types (The Blueprints)</h4>
                        <p>A <code>Service Type</code> is a master template for a business category (e.g., "Food Delivery"). Here, you define the standard conversation flow and the kind of information to collect.</p>
                    </div>
                    <div class="space-y-2">
                        <x-filament::icon icon="heroicon-o-building-storefront" class="w-8 h-8 text-primary-500" />
                        <h4>Providers (The Businesses)</h4>
                        <p>A <code>Provider</code> is a specific business using the platform, like "Slice Pizza." Each Provider is based on a Service Type blueprint and gets its own customized chatbot.</p>
                    </div>
                    <div class="space-y-2">
                        <x-filament::icon icon="heroicon-o-chat-bubble-left-right" class="w-8 h-8 text-primary-500" />
                        <h4>Flows (The Conversations)</h4>
                        <p>A <code>Flow</code> is the actual, step-by-step conversation a customer has on WhatsApp. When you add a new Provider, they get their own copy of a Flow to use and customize.</p>
                    </div>
                </div>

                <h3>How It Works in 3 Steps</h3>
                <ol class="list-decimal space-y-2">
                    <li><strong>Create a Blueprint:</strong> Go to <a href="{{ \App\Filament\Resources\ServiceTypeResource::getUrl() }}" class="text-primary-600 hover:underline">Service Types</a> and create a new blueprint for a business category, like "Retail".</li>
                    <li><strong>Onboard a Business:</strong> Go to <a href="{{ \App\Filament\Resources\ProviderResource::getUrl() }}" class="text-primary-600 hover:underline">Providers</a>, create a new business like "SuperMart", and assign it to the "Retail" Service Type. The system automatically gives SuperMart its own conversation Flow.</li>
                    <li><strong>Go Live:</strong> When a customer messages SuperMart on WhatsApp using a specific keyword, the system uses its unique Flow to manage the conversation automatically.</li>
                </ol>
            </div>
        </x-filament::section>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <a href="{{ \App\Filament\Resources\ServiceTypeResource::getUrl() }}" class="block p-6 bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 quick-link-card">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Manage Service Types</h4>
                <p class="mt-1 text-sm">Define the core business categories for the platform.</p>
            </a>
            <a href="{{ \App\Filament\Resources\ProviderResource::getUrl() }}" class="block p-6 bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 quick-link-card">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Manage Providers</h4>
                <p class="mt-1 text-sm">Onboard and configure the businesses using the service.</p>
            </a>
            <a href="{{ \App\Filament\Resources\FlowResource::getUrl() }}" class="block p-6 bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 quick-link-card">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Build Flows</h4>
                <p class="mt-1 text-sm">Create and edit the conversational flows for providers.</p>
            </a>
        </div>
    </div>
</x-filament-panels::page.simple>