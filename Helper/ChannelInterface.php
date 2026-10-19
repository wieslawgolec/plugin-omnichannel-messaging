<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Helper;

/**
 * Contract for every messaging channel implementation.
 */
interface ChannelInterface
{
    public function getName(): string;

    public function getDisplayName(): string;

    /**
     * Whether this channel supports free-form text outside of a session window.
     */
    public function supportsFreeformOutsideWindow(): bool;

    /**
     * Session / customer-care window length in hours (0 = none).
     */
    public function getWindowHours(): int;

    /**
     * Whether the channel supports official templates (WhatsApp-style).
     */
    public function supportsTemplates(): bool;

    /**
     * Whether inbound webhooks / replies are supported.
     */
    public function supportsInbound(): bool;

    /**
     * Whether media (images, documents, etc.) can be attached.
     */
    public function supportsMedia(): bool;

    /**
     * Send a free-form text message.
     *
     * @param array<string, mixed> $config Channel-specific credentials / settings
     * @return array{success: bool, message_id?: string, error?: string, raw?: array}
     */
    public function sendText(array $config, string $to, string $body, array $options = []): array;

    /**
     * Send a template message (when supported).
     *
     * @param array<string, mixed> $config
     * @param array<string, mixed> $parameters Template body parameters
     * @return array{success: bool, message_id?: string, error?: string, raw?: array}
     */
    public function sendTemplate(array $config, string $to, string $templateName, string $language, array $parameters = [], array $options = []): array;

    /**
     * Validate / parse an inbound webhook payload.
     *
     * @return array{valid: bool, contact_identifier?: string, body?: string, message_id?: string, media?: array, raw?: array}
     */
    public function parseInbound(array $payload, array $config = []): array;
}
