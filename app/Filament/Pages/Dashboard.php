<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\FlowCompletionChart;
use App\Filament\Widgets\SessionsPerDayChart;
use App\Filament\Widgets\StatsOverview;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.dashboard';

    public function getHeaderWidgets(): array
    {
        return [
            StatsOverview::class,
            SessionsPerDayChart::class,
            FlowCompletionChart::class,
        ];
    }
}
