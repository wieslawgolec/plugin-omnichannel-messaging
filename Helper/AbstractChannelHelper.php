<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Helper;

abstract class AbstractChannelHelper implements ChannelInterface
{
    public function supportsFreeformOutsideWindow(): bool
    {
        return false;
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
        return false;
    }

    public function supportsMedia(): bool
    {
        return false;
    }

    public function sendTemplate(array $config, string $to, string $templateName, string $language, array $parameters = [], array $options = []): array
    {
        return [
            'success' => false,
            'error'   => sprintf('%s does not support template messages', $this->getDisplayName()),
        ];
    }

    public function parseInbound(array $payload, array $config = []): array
    {
        return ['valid' => false];
    }

    /**
     * Simple HTTP POST helper used by concrete channels.
     *
     * @param array<string, string> $headers
     * @return array{status: int, body: string, json?: array}
     */
    protected function httpPost(string $url, array $payload, array $headers = [], int $timeout = 30): array
    {
        $ch = curl_init($url);
        if (false === $ch) {
            return ['status' => 0, 'body' => 'curl_init failed'];
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $defaultHeaders = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        $allHeaders = array_merge($defaultHeaders, $headers);

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $allHeaders,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $body   = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno  = curl_errno($ch);
        $error  = curl_error($ch);
        curl_close($ch);

        if (false === $body || $errno > 0) {
            return ['status' => 0, 'body' => $error ?: 'request failed'];
        }

        $decoded = json_decode($body, true);

        return [
            'status' => $status,
            'body'   => $body,
            'json'   => is_array($decoded) ? $decoded : null,
        ];
    }

    /**
     * Normalize phone numbers to digits only (E.164 without +).
     */
    protected function cleanPhone(string $phone): string
    {
        $phone = trim($phone);
        $phone = preg_replace('/[^\d]/', '', $phone) ?? $phone;

        return $phone;
    }
}
