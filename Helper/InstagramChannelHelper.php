<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Helper;

/**
 * Instagram Messaging API (Graph) implementation.
 *
 * Uses the Instagram Messaging product on the same Meta Graph API as Messenger.
 * Recipient is the Instagram-Scoped ID (IGSID). Sender is the Instagram Business
 * account (ig_user_id) linked to a Facebook Page.
 *
 * - Free-form text inside the 24h messaging window
 * - Message tags outside the window (HUMAN_AGENT, etc.)
 * - Inbound webhooks (object: instagram)
 * - Media attachments via URL
 *
 * @see https://developers.facebook.com/docs/messenger-platform/instagram
 */
final class InstagramChannelHelper extends AbstractChannelHelper
{
    public const API_VERSION = 'v21.0';
    public const API_BASE    = 'https://graph.facebook.com/' . self::API_VERSION;

    public function getName(): string
    {
        return 'instagram';
    }

    public function getDisplayName(): string
    {
        return 'Instagram Messaging';
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
        return true; // message tags + generic templates
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
        $igUserId = $config['ig_user_id'] ?? '';
        $token    = $config['page_access_token'] ?? $config['access_token'] ?? '';

        if ('' === $token) {
            return ['success' => false, 'error' => 'Missing page_access_token'];
        }
        if ('' === $igUserId) {
            return ['success' => false, 'error' => 'Missing ig_user_id'];
        }

        $payload = [
            'recipient' => ['id' => $to],
            'message'   => [
                'text' => $body,
            ],
        ];

        // Optional message tag for outside-window sends
        if (!empty($options['tag'])) {
            $payload['messaging_type'] = 'MESSAGE_TAG';
            $payload['tag']            = $options['tag'];
        }

        // Optional media attachment
        if (!empty($options['media_url'])) {
            $mediaType = $options['media_type'] ?? 'image';
            $payload['message'] = [
                'attachment' => [
                    'type'    => $mediaType,
                    'payload' => [
                        'url'         => $options['media_url'],
                        'is_reusable' => (bool) ($options['is_reusable'] ?? true),
                    ],
                ],
            ];
            if ('' !== $body) {
                // caption not supported the same way; send text separately if needed
            }
        }

        $url = self::API_BASE . '/' . rawurlencode($igUserId) . '/messages';

        $response = $this->httpPost($url, $payload, [
            'Authorization: Bearer ' . $token,
        ]);

        return $this->mapResponse($response);
    }

    public function sendTemplate(array $config, string $to, string $templateName, string $language, array $parameters = [], array $options = []): array
    {
        // Instagram uses message tags (like Messenger) rather than WhatsApp named templates.
        $igUserId = $config['ig_user_id'] ?? '';
        $token    = $config['page_access_token'] ?? $config['access_token'] ?? '';

        if ('' === $token) {
            return ['success' => false, 'error' => 'Missing page_access_token'];
        }
        if ('' === $igUserId) {
            return ['success' => false, 'error' => 'Missing ig_user_id'];
        }

        $body = $options['body'] ?? ($parameters[0] ?? '');
        if ('' === $body && empty($options['elements'])) {
            $body = $templateName;
        }

        if (!empty($options['elements'])) {
            $payload = [
                'recipient' => ['id' => $to],
                'message'   => [
                    'attachment' => [
                        'type'    => 'template',
                        'payload' => [
                            'template_type' => 'generic',
                            'elements'      => $options['elements'],
                        ],
                    ],
                ],
            ];
        } else {
            $tag = $templateName ?: ($options['tag'] ?? 'HUMAN_AGENT');
            $payload = [
                'recipient'      => ['id' => $to],
                'messaging_type' => 'MESSAGE_TAG',
                'tag'            => $tag,
                'message'        => [
                    'text' => $body,
                ],
            ];
        }

        $url = self::API_BASE . '/' . rawurlencode($igUserId) . '/messages';

        $response = $this->httpPost($url, $payload, [
            'Authorization: Bearer ' . $token,
        ]);

        return $this->mapResponse($response);
    }

    public function parseInbound(array $payload, array $config = []): array
    {
        // Instagram Messaging webhook (object: instagram) uses the same messaging array shape as Messenger.
        $entry = $payload['entry'][0] ?? null;
        if (!is_array($entry)) {
            return ['valid' => false];
        }

        // Prefer messaging[]; some payloads also use "standby"
        $messaging = $entry['messaging'][0] ?? $entry['standby'][0] ?? null;
        if (!is_array($messaging)) {
            return ['valid' => false];
        }

        $senderId = $messaging['sender']['id'] ?? '';
        if ('' === $senderId) {
            return ['valid' => false];
        }

        $message  = $messaging['message'] ?? null;
        $postback = $messaging['postback'] ?? null;

        $body  = '';
        $msgId = '';
        $media = [];

        if (is_array($message)) {
            $body  = $message['text'] ?? '';
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
            // Story reply / mention
            if ('' === $body && !empty($message['reply_to']['story']['url'])) {
                $body = '[story reply]';
                $media = [
                    'type' => 'story',
                    'url'  => $message['reply_to']['story']['url'],
                ];
            }
        } elseif (is_array($postback)) {
            $body  = $postback['title'] ?? $postback['payload'] ?? '';
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
            $errorMsg = $json['error']['message'] ?? 'Instagram Messaging API error';

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
