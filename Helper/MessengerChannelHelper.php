<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Helper;

/**
 * Facebook Messenger (Send API / Graph) implementation.
 *
 * - Free-form text inside the 24h messaging window
 * - Message tags / templates outside the window (HUMAN_AGENT, ACCOUNT_UPDATE, etc.)
 * - Inbound webhooks (messages, postbacks)
 * - Media attachments via URL
 *
 * Recipient is the Page-Scoped ID (PSID).
 */
final class MessengerChannelHelper extends AbstractChannelHelper
{
    public const API_VERSION = 'v21.0';
    public const API_BASE    = 'https://graph.facebook.com/' . self::API_VERSION;

    public function getName(): string
    {
        return 'messenger';
    }

    public function getDisplayName(): string
    {
        return 'Facebook Messenger';
    }

    public function supportsFreeformOutsideWindow(): bool
    {
        return false;
    }

    public function getWindowHours(): int
    {
        return 24;
    }

    public function supportsTemplates(): bool
    {
        return true; // message tags / structured templates
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
        $pageId = $config['page_id'] ?? '';
        $token  = $config['page_access_token'] ?? '';

        if ('' === $token) {
            return ['success' => false, 'error' => 'Missing page_access_token'];
        }

        $payload = [
            'recipient' => ['id' => $to],
            'messaging_type' => $options['messaging_type'] ?? 'RESPONSE',
            'message' => [
                'text' => $body,
            ],
        ];

        // Optional message tag for outside-window sends (e.g. HUMAN_AGENT)
        if (!empty($options['tag'])) {
            $payload['messaging_type'] = 'MESSAGE_TAG';
            $payload['tag'] = $options['tag'];
        }

        // Optional media attachment
        if (!empty($options['media_url'])) {
            $mediaType = $options['media_type'] ?? 'image';
            $payload['message'] = [
                'attachment' => [
                    'type' => $mediaType,
                    'payload' => [
                        'url' => $options['media_url'],
                        'is_reusable' => (bool) ($options['is_reusable'] ?? true),
                    ],
                ],
            ];
        }

        $url = self::API_BASE . '/me/messages';
        if ('' !== $pageId) {
            $url = self::API_BASE . '/' . rawurlencode($pageId) . '/messages';
        }

        $response = $this->httpPost($url, $payload, [
            'Authorization: Bearer ' . $token,
        ]);

        return $this->mapResponse($response);
    }

    public function sendTemplate(array $config, string $to, string $templateName, string $language, array $parameters = [], array $options = []): array
    {
        // Messenger uses message tags or generic templates rather than WhatsApp-style named templates.
        // templateName is treated as a message tag (HUMAN_AGENT, ACCOUNT_UPDATE, POST_PURCHASE_UPDATE, …)
        // or as a generic template title when options['elements'] is provided.
        $pageId = $config['page_id'] ?? '';
        $token  = $config['page_access_token'] ?? '';

        if ('' === $token) {
            return ['success' => false, 'error' => 'Missing page_access_token'];
        }

        $body = $options['body'] ?? ($parameters[0] ?? '');
        if ('' === $body && empty($options['elements'])) {
            $body = $templateName; // fallback
        }

        if (!empty($options['elements'])) {
            // Generic template
            $payload = [
                'recipient' => ['id' => $to],
                'messaging_type' => 'RESPONSE',
                'message' => [
                    'attachment' => [
                        'type' => 'template',
                        'payload' => [
                            'template_type' => 'generic',
                            'elements' => $options['elements'],
                        ],
                    ],
                ],
            ];
        } else {
            // Message tag send (outside 24h window)
            $tag = $templateName ?: ($options['tag'] ?? 'HUMAN_AGENT');
            $payload = [
                'recipient' => ['id' => $to],
                'messaging_type' => 'MESSAGE_TAG',
                'tag' => $tag,
                'message' => [
                    'text' => $body,
                ],
            ];
        }

        $url = self::API_BASE . '/me/messages';
        if ('' !== $pageId) {
            $url = self::API_BASE . '/' . rawurlencode($pageId) . '/messages';
        }

        $response = $this->httpPost($url, $payload, [
            'Authorization: Bearer ' . $token,
        ]);

        return $this->mapResponse($response);
    }

    public function parseInbound(array $payload, array $config = []): array
    {
        // Standard Messenger webhook shape
        $entry = $payload['entry'][0] ?? null;
        if (!is_array($entry)) {
            return ['valid' => false];
        }

        $messaging = $entry['messaging'][0] ?? null;
        if (!is_array($messaging)) {
            return ['valid' => false];
        }

        $senderId = $messaging['sender']['id'] ?? '';
        if ('' === $senderId) {
            return ['valid' => false];
        }

        $message = $messaging['message'] ?? null;
        $postback = $messaging['postback'] ?? null;

        $body = '';
        $msgId = '';
        $media = [];

        if (is_array($message)) {
            $body = $message['text'] ?? '';
            $msgId = $message['mid'] ?? '';
            if (!empty($message['attachments'][0])) {
                $att = $message['attachments'][0];
                $media = [
                    'type' => $att['type'] ?? 'file',
                    'url'  => $att['payload']['url'] ?? null,
                ];
                if ('' === $body && isset($att['payload']['title'])) {
                    $body = (string) $att['payload']['title'];
                }
            }
        } elseif (is_array($postback)) {
            $body = $postback['title'] ?? $postback['payload'] ?? '';
            $msgId = $postback['mid'] ?? '';
        } else {
            return ['valid' => false];
        }

        return [
            'valid'              => true,
            'contact_identifier' => $senderId,
            'body'               => is_string($body) ? $body : '',
            'message_id'         => is_string($msgId) ? $msgId : '',
            'media'              => $media,
            'raw'                => $messaging,
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
            $errorMsg = $json['error']['message'] ?? 'Messenger API error';

            return [
                'success' => false,
                'error'   => $errorMsg,
                'raw'     => $json,
            ];
        }

        $msgId = $json['message_id'] ?? null;

        return [
            'success'    => true,
            'message_id' => is_string($msgId) ? $msgId : null,
            'raw'        => $json,
        ];
    }
}
