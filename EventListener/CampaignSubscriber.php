<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\EventListener;

use MauticPlugin\MauticOmnichannelMessagingBundle\Helper\MessageSender;

/**
 * Registers the "Send Omnichannel Message" campaign action and executes it.
 *
 * Note: In a full Mautic environment the lead fields and integration config
 * would be resolved from the container. Here the subscriber focuses on the
 * pure decision logic that can be unit-tested without Symfony.
 */
final class CampaignSubscriber
{
    public function __construct(
        private readonly MessageSender $messageSender,
    ) {
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'mautic.campaign_on_build' => ['onCampaignBuild', 0],
        ];
    }

    /**
     * Execute the send. Called by the campaign engine via the event name.
     * Kept public so unit tests can invoke it directly.
     *
     * @param array<string, mixed> $config   Action properties from the campaign
     * @param array<string, mixed> $channelConfig Credentials for the chosen channel
     * @param string               $to       Recipient identifier (phone, chat_id, userid, openid…)
     * @return array{success: bool, message_id?: string, error?: string, used_template?: bool}
     */
    public function executeSend(
        array $config,
        array $channelConfig,
        string $to,
        ?\DateTimeInterface $lastInbound = null,
    ): array {
        $channelName = $config['channel'] ?? '';
        $body        = $config['message'] ?? '';
        $options     = [
            'template_name' => $config['template_name'] ?? '',
            'language'      => $config['language'] ?? 'en_US',
            'parameters'    => $config['parameters'] ?? [],
        ];

        if ('' !== ($config['to_override'] ?? '')) {
            $to = (string) $config['to_override'];
        }

        return $this->messageSender->send(
            $channelName,
            $channelConfig,
            $to,
            $body,
            $lastInbound,
            $options
        );
    }
}
