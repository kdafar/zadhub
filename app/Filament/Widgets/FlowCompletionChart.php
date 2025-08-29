<?php

namespace App\Filament\Widgets;

use App\Models\WhatsappSession;
use Filament\Widgets\ChartWidget;

class FlowCompletionChart extends ChartWidget
{
    protected static ?string $heading = 'Flow Completion Status';

    protected static ?string $pollingInterval = '30s';

    protected function getData(): array
    {
        $data = WhatsappSession::query()
            ->select('status')
            ->get()
            ->groupBy('status')
            ->map(function ($group) {
                return count($group);
            });

        return [
            'datasets' => [
                [
                    'label' => 'Sessions',
                    'data' => $data->values(),
                    'backgroundColor' => ['#2ecc71', '#f1c40f', '#e74c3c', '#95a5a6'],
                ],
            ],
            'labels' => $data->keys()->map(fn ($status) => ucfirst($status)),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
