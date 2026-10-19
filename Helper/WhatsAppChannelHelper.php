<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Helper;

/**
 * WhatsApp Business Cloud API (Graph) implementation.
 *
 * Supports:
 * - Free-form text inside 24h customer care window
 * - Template messages (outside window)
 * - Inbound webhooks
 * - Media (links)
 */
final class WhatsAppChannelHelper extends AbstractChannelHelper
{
    public const API_VERSION = 'v21.0';
    public const API_BASE    = 'https://graph.facebook.com/' . self::API_VERSION;

    public function getName(): string
    {
        return 'whatsapp';
    }

    public function getDisplayName(): string
    {
        return 'WhatsApp';
    }

    public function supportsFreeformOutsideWindow(): bool
    {
        return false; // only templates outside the 24h window
    }

    public function getWindowHours(): int
    {
        return 24;
    }

    public function supportsTemplates(): bool
    {
        return true;
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
        $phoneNumberId = $config['phone_number_id'] ?? '';
        $token         = $config['access_token'] ?? '';

        if ('' === $phoneNumberId || '' === $token) {
            return ['success' => false, 'error' => 'Missing phone_number_id or access_token'];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $this->cleanPhone($to),
            'type'              => 'text',
            'text'              => [
                'body'        => $body,
                'preview_url' => (bool) ($options['preview_url'] ?? false),
            ],
        ];

        $url = self::API_BASE . '/' . rawurlencode($phoneNumberId) . '/messages';

        $response = $this->httpPost($url, $payload, [
            'Authorization: Bearer ' . $token,
        ]);

        return $this->mapResponse($response);
    }

    public function sendTemplate(array $config, string $to, string $templateName, string $language, array $parameters = [], array $options = []): array
    {
        $phoneNumberId = $config['phone_number_id'] ?? '';
        $token         = $config['access_token'] ?? '';

        if ('' === $phoneNumberId || '' === $token) {
            return ['success' => false, 'error' => 'Missing phone_number_id or access_token'];
        }

        $template = [
            'name'     => $templateName,
            'language' => ['code' => $language ?: 'en_US'],
        ];

        if ([] !== $parameters) {
            $template['components'] = [[
                'type'       => 'body',
                'parameters' => array_map(
                    static fn (string $v) => ['type' => 'text', 'text' => $v],
                    array_values($parameters)
                ),
            ]];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $this->cleanPhone($to),
            'type'              => 'template',
            'template'          => $template,
        ];

        $url = self::API_BASE . '/' . rawurlencode($phoneNumberId) . '/messages';

        $response = $this->httpPost($url, $payload, [
            'Authorization: Bearer ' . $token,
        ]);

        return $this->mapResponse($response);
    }

    public function parseInbound(array $payload, array $config = []): array
    {
        // Standard WhatsApp Cloud API webhook shape
        $entry = $payload['entry'][0] ?? null;
        if (!is_array($entry)) {
            return ['valid' => false];
        }

        $change = $entry['changes'][0] ?? null;
        if (!is_array($change) || ($change['field'] ?? '') !== 'messages') {
            return ['valid' => false];
        }

        $value   = $change['value'] ?? [];
        $message = $value['messages'][0] ?? null;
        if (!is_array($message)) {
            return ['valid' => false];
        }

        $from = $message['from'] ?? '';
        $id   = $message['id'] ?? '';
        $type = $message['type'] ?? 'text';
        $body = '';

        if ('text' === $type) {
            $body = $message['text']['body'] ?? '';
        } elseif (isset($message[$type]['caption'])) {
            $body = (string) $message[$type]['caption'];
        }

        $media = [];
        if (in_array($type, ['image', 'video', 'document', 'audio'], true)) {
            $media = [
                'type' => $type,
                'id'   => $message[$type]['id'] ?? null,
                'mime' => $message[$type]['mime_type'] ?? null,
            ];
        }

        return [
            'valid'              => true,
            'contact_identifier' => $from,
            'body'               => $body,
            'message_id'         => $id,
            'media'              => $media,
            'raw'                => $message,
        ];
    }

    /**
     * @param array{status: int, body: string, json?: array|null} $response
     * @return array{success: bool, message_id?: string, error?: string, raw?: array}
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

        if (($response['status'] ?? 0) >= 400) {
            $errorMsg = $json['error']['message'] ?? $json['error']['error_user_msg'] ?? 'API error';

            return [
                'success' => false,
                'error'   => $errorMsg,
                'raw'     => $json,
            ];
        }

        $msgId = $json['messages'][0]['id'] ?? null;

        return [
            'success'    => true,
            'message_id' => is_string($msgId) ? $msgId : null,
            'raw'        => $json,
        ];
    }
}
