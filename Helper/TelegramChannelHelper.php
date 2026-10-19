<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Helper;

/**
 * Telegram Bot API implementation.
 *
 * Supports free-form messages at any time, inbound via webhook, media via links.
 */
final class TelegramChannelHelper extends AbstractChannelHelper
{
    public const API_BASE = 'https://api.telegram.org';

    public function getName(): string
    {
        return 'telegram';
    }

    public function getDisplayName(): string
    {
        return 'Telegram';
    }

    public function supportsFreeformOutsideWindow(): bool
    {
        return true;
    }

    public function getWindowHours(): int
    {
        return 0; // no session window
    }

    public function supportsTemplates(): bool
    {
        return false;
    }

    public function supportsInbound(): bool
    {
        return true;
    }

    public function supportsMedia(): bool
    {
        return true;
    }

    public function sendText(array $config, string $to, string $body, array $options = []): array
    {
        $token = $config['bot_token'] ?? '';
        if ('' === $token) {
            return ['success' => false, 'error' => 'Missing bot_token'];
        }

        $chatId = $to; // Telegram uses chat_id (can be numeric or @username)
        if (isset($options['chat_id']) && '' !== (string) $options['chat_id']) {
            $chatId = (string) $options['chat_id'];
        }

        $payload = [
            'chat_id'                  => $chatId,
            'text'                     => $body,
            'parse_mode'               => $options['parse_mode'] ?? 'HTML',
            'disable_web_page_preview' => (bool) ($options['disable_preview'] ?? false),
        ];

        if (!empty($options['reply_markup'])) {
            $payload['reply_markup'] = $options['reply_markup'];
        }

        $url      = self::API_BASE . '/bot' . $token . '/sendMessage';
        $response = $this->httpPost($url, $payload);

        return $this->mapResponse($response);
    }

    public function parseInbound(array $payload, array $config = []): array
    {
        $message = $payload['message'] ?? $payload['edited_message'] ?? null;
        if (!is_array($message)) {
            return ['valid' => false];
        }

        $chat   = $message['chat'] ?? [];
        $from   = $message['from'] ?? [];
        $text   = $message['text'] ?? $message['caption'] ?? '';
        $msgId  = isset($message['message_id']) ? (string) $message['message_id'] : '';
        $chatId = isset($chat['id']) ? (string) $chat['id'] : '';

        $media = [];
        foreach (['photo', 'document', 'video', 'audio', 'voice'] as $type) {
            if (isset($message[$type])) {
                $media = [
                    'type' => $type,
                    'data' => $message[$type],
                ];
                break;
            }
        }

        return [
            'valid'              => true,
            'contact_identifier' => $chatId,
            'body'               => is_string($text) ? $text : '',
            'message_id'         => $msgId,
            'media'              => $media,
            'raw'                => $message,
            'from_user'          => $from,
        ];
    }

    /**
     * @param array{status: int, body: string, json?: array|null} $response
     */
    private function mapResponse(array $response): array
    {
        $json = $response['json'] ?? null;
        if (!is_array($json)) {
            return [
                'success' => false,
                'error'   => 'Invalid response: ' . ($response['body'] ?? 'empty'),
            ];
        }

        if (!($json['ok'] ?? false)) {
            return [
                'success' => false,
                'error'   => $json['description'] ?? 'Telegram API error',
                'raw'     => $json,
            ];
        }

        $result = $json['result'] ?? [];
        $msgId  = isset($result['message_id']) ? (string) $result['message_id'] : null;

        return [
            'success'    => true,
            'message_id' => $msgId,
            'raw'        => $json,
        ];
    }
}
