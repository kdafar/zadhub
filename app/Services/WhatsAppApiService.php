<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Netflie\WhatsAppCloudApi\Message\AudioMessage;
use Netflie\WhatsAppCloudApi\Message\ButtonReply;
use Netflie\WhatsAppCloudApi\Message\ContactMessage;
use Netflie\WhatsAppCloudApi\Message\DocumentMessage;
use Netflie\WhatsAppCloudApi\Message\ImageMessage;
use Netflie\WhatsAppCloudApi\Message\InteractiveMessage\Action;
use Netflie\WhatsAppCloudApi\Message\InteractiveMessage\Body;
use Netflie\WhatsAppCloudApi\Message\InteractiveMessage\Footer;
use Netflie\WhatsAppCloudApi\Message\InteractiveMessage\Header;
use Netflie\WhatsAppCloudApi\Message\InteractiveMessage\HeaderType;
use Netflie\WhatsAppCloudApi\Message\LocationMessage;
use Netflie\WhatsAppCloudApi\Message\Options;
use Netflie\WhatsAppCloudApi\Message\StickerMessage;
use Netflie\WhatsAppCloudApi\Message\Template\Component;
use Netflie\WhatsAppCloudApi\Message\VideoMessage;
use Netflie\WhatsAppCloudApi\WhatsAppCloudApi;

class WhatsAppApiService
{
    public function __construct(protected WhatsAppCloudApi $client)
    {}

    public function sendTextMessage(string $to, string $message, bool $previewUrl = false): void
    {
        try {
            $this->client->sendTextMessage($to, $message, $previewUrl);
            Log::info('WhatsApp text message sent successfully.', ['to' => $to]);
        } catch (\Throwable $e) {
            Log::error('Exception while sending WhatsApp text message.', ['error' => $e->getMessage()]);
        }
    }

public function sendImage(string $to, string $linkOrId, string $caption = ''): void
{
    // 1. Create the ImageMessage with only the link or ID
    $imageMessage = new ImageMessage($linkOrId);

    // 2. If a caption exists, set it on the object
    if (!empty($caption)) {
        $imageMessage->setCaption($caption);
    }

    // 3. Pass the fully constructed object to the sendMedia method
    $this->sendMedia($imageMessage, $to);
}


    public function sendDocument(string $to, string $linkOrId, string $filename, string $caption = ''): void
    {
        $this->sendMedia(new DocumentMessage($linkOrId, $filename, $caption), $to);
    }

    public function sendAudio(string $to, string $linkOrId): void
    {
        $this->sendMedia(new AudioMessage($linkOrId), $to);
    }

    public function sendVideo(string $to, string $linkOrId, string $caption = ''): void
    {
        $this->sendMedia(new VideoMessage($linkOrId, $caption), $to);
    }

    public function sendSticker(string $to, string $linkOrId): void
    {
        $this->sendMedia(new StickerMessage($linkOrId), $to);
    }

    private function sendMedia(object $mediaMessage, string $to): void
    {
        try {
            $this->client->sendMedia($to, $mediaMessage);
            Log::info('WhatsApp media message sent successfully.', ['to' => $to, 'type' => get_class($mediaMessage)]);
        } catch (\Throwable $e) {
            Log::error('Exception while sending WhatsApp media message.', ['error' => $e->getMessage()]);
        }
    }

    public function sendLocation(string $to, float $latitude, float $longitude, string $name = '', string $address = ''): void
    {
        try {
            $this->client->sendLocation($to, new LocationMessage($latitude, $longitude, $name, $address));
            Log::info('WhatsApp location message sent successfully.', ['to' => $to]);
        } catch (\Throwable $e) {
            Log::error('Exception while sending WhatsApp location message.', ['error' => $e->getMessage()]);
        }
    }

    public function sendTemplate(string $to, string $templateName, string $language, ?Component $components = null): void
    {
        try {
            $this->client->sendTemplate($to, $templateName, $language, $components);
            Log::info('WhatsApp template message sent successfully.', ['to' => $to, 'template' => $templateName]);
        } catch (\Throwable $e) {
            Log::error('Exception while sending WhatsApp template message.', ['error' => $e->getMessage()]);
        }
    }

    public function sendButtonMessage(string $to, string $question, array $buttons): void
    {
        try {
            $replyButtons = array_map(fn ($button) => new ButtonReply($button['id'], $button['label']), $buttons);
            $action = new Action($replyButtons);
            $body = new Body($question);
            $interactiveMessage = new \Netflie\WhatsAppCloudApi\Message\InteractiveMessage\InteractiveMessage($action, $body);

            $this->client->sendInteractiveMessage($to, $interactiveMessage);
            Log::info('WhatsApp button message sent successfully.', ['to' => $to]);
        } catch (\Throwable $e) {
            Log::error('Exception while sending WhatsApp button message.', ['error' => $e->getMessage()]);
        }
    }

    public function markMessageAsRead(string $messageId): void
    {
        try {
            $this->client->markAsRead($messageId);
        } catch (\Throwable $e) {
            Log::error('Exception while marking message as read.', ['error' => $e->getMessage()]);
        }
    }

    public function sendFlowMessage(string $to, string $flowId, string $flowToken, array $screenData): void
    {
        try {
            $header = new Header(HeaderType::TEXT, $screenData['title'] ?? ' ');
            $body = new Body($screenData['body'] ?? ' ');
            $footer = new Footer($screenData['footer'] ?? ' ');

            $action = new \Netflie\WhatsAppCloudApi\Message\Flow\Action(
                $screenData['footer'] ?? 'Next',
                new \Netflie\WhatsAppCloudApi\Message\Flow\Parameters(
                    $flowId,
                    $flowToken,
                    [
                        'screen' => $screenData['id'],
                        'data' => $screenData['data_bindings'] ?? [],
                    ]
                )
            );

            $interactive = new \Netflie\WhatsAppCloudApi\Message\Flow\FlowMessage($header, $body, $footer, $action);
            $options = (new Options())->setPreviewUrl(false);

            $this->client->sendInteractiveMessage($to, $interactive, $options);
            Log::info('WhatsApp flow message sent successfully.', ['to' => $to]);
        } catch (\Throwable $e) {
            Log::error('Exception while sending WhatsApp flow message.', ['error' => $e->getMessage()]);
        }
    }
}
