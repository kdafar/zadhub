<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class WhatsAppApiServiceFake
{
    public function __construct(
        protected string $apiToken = 'fake',
        protected string $phoneNumberId = 'fake'
    ) {}

    public function sendTextMessage(string $to, string $text): void
    {
        Log::channel('single')->info('[FAKE WA] sendText', compact('to', 'text'));
    }

    public function sendFlowMessage(string $to, string $flowId, string $flowToken, array $screenData): void
    {
        Log::channel('single')->info('[FAKE WA] sendFlow', compact('to', 'flowId', 'flowToken', 'screenData'));
    }

    // if you added sendList/sendImage in FlowRenderer, mirror them here:
    public function sendImage(string $to, string $media, ?string $caption = null): void
    {
        Log::channel('single')->info('[FAKE WA] sendImage', compact('to', 'media', 'caption'));
    }

    public function sendList(string $to, string $header, string $body, array $rows, ?string $footer = null): void
    {
        Log::channel('single')->info('[FAKE WA] sendList', compact('to', 'header', 'body', 'rows', 'footer'));
    }
}
