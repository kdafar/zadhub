<?php

namespace App\Filament\Widgets;

use App\Models\Flow;
use App\Models\Provider;
use App\Models\ServiceType;
use App\Models\WhatsappSession;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Service Types', ServiceType::query()->count())
                ->description('Total business categories')
                ->icon('heroicon-o-cog-6-tooth'),
            Stat::make('Providers', Provider::query()->count())
                ->description('Total onboarded businesses')
                ->icon('heroicon-o-building-storefront'),
            Stat::make('Active Flows', Flow::query()->where('is_active', true)->count())
                ->description('Total active conversations')
                ->icon('heroicon-o-arrows-right-left'),
            Stat::make('Today\'s Sessions', WhatsappSession::query()->whereDate('created_at', today())->count())
                ->description('WhatsApp conversations today')
                ->icon('heroicon-o-chat-bubble-left-right'),
        ];
    }
}
