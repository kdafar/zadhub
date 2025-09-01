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

    public function hasLogo(): bool
    {
        return false; // Set to false to hide the logo and fix the error
    }

    public function getLogo(): ?string
    {
        return null;
    }

    public function getLogoHeight(): ?string
    {
        return null;
    }
}
