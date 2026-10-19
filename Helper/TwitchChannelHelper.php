<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Helper;

/**
 * Twitch chat messaging via Helix Chat API.
 *
 * Sends chat messages as the authorized user/bot into a broadcaster's channel.
 * Free-form at any time (no customer-care window). Inbound requires EventSub
 * (not covered by a simple webhook parse in the same way as Messenger).
 *
 * Config:
 * - access_token (OAuth user token with chat:edit / user:write:chat)
 * - client_id
 * - broadcaster_id (default channel to post into; can be overridden by $to)
 * - sender_id (optional; defaults to the token's user)
 */
final class TwitchChannelHelper extends AbstractChannelHelper
{
    public const API_BASE = 'https://api.twitch.tv/helix';

    public function getName(): string
    {
        return 'twitch';
    }

    public function getDisplayName(): string
    {
        return 'Twitch';
    }

    public function supportsFreeformOutsideWindow(): bool
    {
        return true;
    }

    public function getWindowHours(): int
    {
        return 0;
    }

    public function supportsTemplates(): bool
    {
        return false;
    }

    public function supportsInbound(): bool
    {
        // EventSub can deliver chat messages; basic webhook parse is supported
        // for the channel.chat.message notification shape.
        return true;
    }

    public function supportsMedia(): bool
    {
        return false; // chat is text-only via Helix send endpoint
    }

    public function sendText(array $config, string $to, string $body, array $options = []): array
    {
        $token        = $config['access_token'] ?? '';
        $clientId     = $config['client_id'] ?? '';
        $broadcasterId = $to !== '' ? $to : ($config['broadcaster_id'] ?? '');
        $senderId     = $options['sender_id'] ?? ($config['sender_id'] ?? $broadcasterId);

        if ('' === $token || '' === $clientId) {
            return ['success' => false, 'error' => 'Missing access_token or client_id'];
        }
        if ('' === $broadcasterId) {
            return ['success' => false, 'error' => 'Missing broadcaster_id (or $to)'];
        }

        $payload = [
            'broadcaster_id' => $broadcasterId,
            'sender_id'      => $senderId,
            'message'        => $body,
        ];

        $url = self::API_BASE . '/chat/messages';

        $response = $this->httpPost($url, $payload, [
            'Authorization: Bearer ' . $token,
            'Client-Id: ' . $clientId,
        ]);

        return $this->mapResponse($response);
    }

    public function parseInbound(array $payload, array $config = []): array
    {
        // EventSub channel.chat.message notification
        // https://dev.twitch.tv/docs/eventsub/eventsub-subscription-types/#channelchatmessage
        $event = $payload['event'] ?? $payload;
        if (!is_array($event)) {
            return ['valid' => false];
        }

        $text = $event['message']['text'] ?? $event['message_text'] ?? null;
        $chatterId = $event['chatter_user_id'] ?? $event['user_id'] ?? '';
        $msgId = $event['message_id'] ?? '';

        if (!is_string($text) || '' === $chatterId) {
            // Fallback: classic IRC-style or simpler webhook
            if (isset($payload['username'], $payload['message'])) {
                return [
                    'valid'              => true,
                    'contact_identifier' => (string) $payload['username'],
                    'body'               => (string) $payload['message'],
                    'message_id'         => isset($payload['id']) ? (string) $payload['id'] : '',
                    'media'              => [],
                    'raw'                => $payload,
                ];
            }

            return ['valid' => false];
        }

        return [
            'valid'              => true,
            'contact_identifier' => (string) $chatterId,
            'body'               => $text,
            'message_id'         => is_string($msgId) ? $msgId : '',
            'media'              => [],
            'raw'                => $event,
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
            $errorMsg = $json['message'] ?? $json['error'] ?? 'Twitch API error';
            if (is_array($errorMsg)) {
                $errorMsg = json_encode($errorMsg);
            }

            return [
                'success' => false,
                'error'   => (string) $errorMsg,
                'raw'     => $json,
            ];
        }

        // Helix returns data[0].message_id on success
        $data = $json['data'][0] ?? null;
        $msgId = is_array($data) ? ($data['message_id'] ?? null) : null;

        return [
            'success'    => true,
            'message_id' => is_string($msgId) ? $msgId : null,
            'raw'        => $json,
        ];
    }
}
