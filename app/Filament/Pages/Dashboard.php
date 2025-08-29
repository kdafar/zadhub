<?php

namespace App\Filament\Pages;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.dashboard';

    // Add these methods to satisfy the 'simple' page layout requirements
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