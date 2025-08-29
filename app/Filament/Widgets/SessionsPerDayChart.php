<?php

namespace App\Filament\Widgets;

use App\Models\WhatsappSession;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class SessionsPerDayChart extends ChartWidget
{
    protected static ?string $heading = 'WhatsApp Sessions (Last 30 Days)';

    protected static ?string $pollingInterval = '30s';

    protected function getData(): array
    {
        $data = WhatsappSession::query()
            ->where('created_at', '>=', now()->subDays(30))
            ->select('created_at')
            ->get()
            ->groupBy(function ($session) {
                return Carbon::parse($session->created_at)->format('M d');
            })
            ->map(function ($group) {
                return count($group);
            });

        return [
            'datasets' => [
                [
                    'label' => 'Sessions',
                    'data' => $data->values(),
                    'borderColor' => '#3498db',
                    'tension' => 0.1,
                ],
            ],
            'labels' => $data->keys(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
