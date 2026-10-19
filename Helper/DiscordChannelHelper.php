<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Helper;

/**
 * Discord messaging via Incoming Webhook or Bot token.
 *
 * Primarily one-way broadcast; limited inbound support via bot interactions.
 */
final class DiscordChannelHelper extends AbstractChannelHelper
{
    public function getName(): string
    {
        return 'discord';
    }

    public function getDisplayName(): string
    {
        return 'Discord';
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
        return false; // interactions require a running bot process
    }

    public function supportsMedia(): bool
    {
        return true;
    }

    public function sendText(array $config, string $to, string $body, array $options = []): array
    {
        // Prefer webhook if configured
        $webhookUrl = $config['webhook_url'] ?? '';
        if ('' !== $webhookUrl) {
            return $this->sendViaWebhook($webhookUrl, $body, $options);
        }

        $botToken  = $config['bot_token'] ?? '';
        $channelId = $to ?: ($config['default_channel_id'] ?? '');
        if ('' === $botToken || '' === $channelId) {
            return ['success' => false, 'error' => 'Missing webhook_url or (bot_token + channel_id)'];
        }

        $payload = [
            'content' => $body,
        ];
        if (!empty($options['embeds'])) {
            $payload['embeds'] = $options['embeds'];
        }

        $url      = 'https://discord.com/api/v10/channels/' . rawurlencode($channelId) . '/messages';
        $response = $this->httpPost($url, $payload, [
            'Authorization: Bot ' . $botToken,
        ]);

        return $this->mapBotResponse($response);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function sendViaWebhook(string $webhookUrl, string $body, array $options): array
    {
        $payload = [
            'content' => $body,
        ];
        if (!empty($options['username'])) {
            $payload['username'] = $options['username'];
        }
        if (!empty($options['avatar_url'])) {
            $payload['avatar_url'] = $options['avatar_url'];
        }
        if (!empty($options['embeds'])) {
            $payload['embeds'] = $options['embeds'];
        }

        $response = $this->httpPost($webhookUrl, $payload);

        // Discord webhooks return 204 No Content on success
        if (($response['status'] ?? 0) === 204 || ($response['status'] ?? 0) === 200) {
            return [
                'success'    => true,
                'message_id' => null,
                'raw'        => $response['json'] ?? [],
            ];
        }

        $json = $response['json'] ?? [];

        return [
            'success' => false,
            'error'   => is_array($json) ? ($json['message'] ?? 'Webhook error') : 'Webhook error',
            'raw'     => is_array($json) ? $json : [],
        ];
    }

    /**
     * @param array{status: int, body: string, json?: array|null} $response
     */
    private function mapBotResponse(array $response): array
    {
        $json = $response['json'] ?? null;
        if (!is_array($json)) {
            return [
                'success' => false,
                'error'   => 'Invalid response: ' . ($response['body'] ?? 'empty'),
            ];
        }

        if (($response['status'] ?? 0) >= 400) {
            return [
                'success' => false,
                'error'   => $json['message'] ?? 'Discord API error',
                'raw'     => $json,
            ];
        }

        return [
            'success'    => true,
            'message_id' => isset($json['id']) ? (string) $json['id'] : null,
            'raw'        => $json,
        ];
    }
}
