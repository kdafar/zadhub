<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppApiService
{
    protected string $token;

    protected string $phoneNumberId;

    public function __construct(string $token, string $phoneNumberId)
    {
        $this->token = $token;
        $this->phoneNumberId = $phoneNumberId;
    }

    public function sendTextMessage(string $to, string $message): void
    {
        $this->sendMessage($to, [
            'type' => 'text',
            'text' => ['body' => $message],
        ]);
    }

    public function sendButtonMessage(string $to, string $question, array $buttons): void
    {
        // WhatsApp interactive buttons have a specific format
        $action = [
            'buttons' => array_map(function ($button, $index) {
                return [
                    'type' => 'reply',
                    'reply' => [
                        'id' => "btn_{$index}", // Simple ID for now
                        'title' => $button['label'],
                    ],
                ];
            }, $buttons, array_keys($buttons)),
        ];

        $this->sendMessage($to, [
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $question],
                'action' => $action,
            ],
        ]);
    }

    public function markMessageAsRead(string $messageId): void
    {
        $this->sendMessage(null, [
            'status' => 'read',
            'message_id' => $messageId,
        ]);
    }

    private function sendMessage(?string $to, array $payload): void
    {
        Log::info('Inside sendMessage method.');
        try {
            $url = "https://graph.facebook.com/v19.0/{$this->phoneNumberId}/messages";

            $basePayload = ['messaging_product' => 'whatsapp'];
            if ($to) {
                $basePayload['to'] = $to;
            }

            $response = Http::withToken($this->token)->post($url, $basePayload + $payload);

            // Log the full response from Meta for debugging
            Log::info('WhatsApp API Response:', [
                'status' => $response->status(),
                'json' => $response->json(),
            ]);

            if ($response->failed()) {
                Log::error('WhatsApp API request failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            } elseif ($response->json('error')) {
                Log::error('WhatsApp API returned an error.', [
                    'status' => $response->status(),
                    'error' => $response->json('error'),
                ]);
            } else {
                Log::info('WhatsApp message sent successfully.', ['to' => $to]);
            }
        } catch (\Throwable $e) {
            Log::error('Exception while sending WhatsApp message.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function sendFlowMessage(string $to, string $flowId, string $flowToken, array $screenData): void
    {
        $this->sendMessage($to, [
            'type' => 'interactive',
            'interactive' => [
                'type' => 'flow',
                'header' => ['type' => 'text', 'text' => $screenData['title'] ?? ' '],
                'body' => ['text' => $screenData['body'] ?? ' '],
                'footer' => ['text' => $screenData['footer'] ?? ' '],
                'action' => [
                    'name' => 'flow',
                    'parameters' => [
                        'flow_message_version' => '3',
                        'flow_id' => $flowId,
                        'flow_token' => $flowToken,
                        'flow_cta' => $screenData['footer'] ?? 'Next',
                        'flow_action' => 'navigate',
                        'flow_action_payload' => [
                            'screen' => $screenData['id'],
                            'data' => $screenData['data_bindings'] ?? [],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
