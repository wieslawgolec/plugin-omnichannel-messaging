<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Helper;

/**
 * Central registry of available messaging channels.
 */
final class ChannelRegistry
{
    /** @var array<string, ChannelInterface> */
    private array $channels = [];

    public function __construct()
    {
        $this->register(new WhatsAppChannelHelper());
        $this->register(new TelegramChannelHelper());
        $this->register(new WeComChannelHelper());
        $this->register(new WeChatChannelHelper());
        $this->register(new DiscordChannelHelper());
        $this->register(new MessengerChannelHelper());
        $this->register(new TwitchChannelHelper());
        $this->register(new InstagramChannelHelper());
    }

    public function register(ChannelInterface $channel): void
    {
        $this->channels[$channel->getName()] = $channel;
    }

    public function get(string $name): ?ChannelInterface
    {
        return $this->channels[$name] ?? null;
    }

    /**
     * @return array<string, ChannelInterface>
     */
    public function all(): array
    {
        return $this->channels;
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->channels);
    }

    /**
     * Channels that support templates.
     *
     * @return list<ChannelInterface>
     */
    public function withTemplates(): array
    {
        return array_values(array_filter(
            $this->channels,
            static fn (ChannelInterface $c) => $c->supportsTemplates()
        ));
    }

    /**
     * Channels that support inbound webhooks.
     *
     * @return list<ChannelInterface>
     */
    public function withInbound(): array
    {
        return array_values(array_filter(
            $this->channels,
            static fn (ChannelInterface $c) => $c->supportsInbound()
        ));
    }
}
