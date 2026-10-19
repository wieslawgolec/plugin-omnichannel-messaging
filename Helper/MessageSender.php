<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Helper;

/**
 * High-level facade that chooses the right channel, respects session windows,
 * and decides between free-form text vs template.
 */
final class MessageSender
{
    public function __construct(
        private readonly ChannelRegistry $registry,
        private readonly WindowManager $windowManager = new WindowManager(),
    ) {
    }

    /**
     * Send a message, automatically falling back to template when the window is closed.
     *
     * @param array<string, mixed> $channelConfig Credentials for the channel
     * @param array<string, mixed> $options       Extra options (template_name, language, parameters, ...)
     * @return array{success: bool, message_id?: string, error?: string, used_template?: bool, raw?: array}
     */
    public function send(
        string $channelName,
        array $channelConfig,
        string $to,
        string $body,
        ?\DateTimeInterface $lastInbound = null,
        array $options = [],
    ): array {
        $channel = $this->registry->get($channelName);
        if (null === $channel) {
            return ['success' => false, 'error' => 'Unknown channel: ' . $channelName];
        }

        $mustTemplate = $this->windowManager->mustUseTemplate($lastInbound, $channel);

        if ($mustTemplate) {
            $templateName = $options['template_name'] ?? $options['template'] ?? '';
            if ('' === $templateName) {
                return [
                    'success' => false,
                    'error'   => sprintf(
                        '%s session window is closed (%dh). Provide a template_name.',
                        $channel->getDisplayName(),
                        $channel->getWindowHours()
                    ),
                ];
            }

            $result = $channel->sendTemplate(
                $channelConfig,
                $to,
                $templateName,
                $options['language'] ?? 'en_US',
                $options['parameters'] ?? [],
                $options
            );
            $result['used_template'] = true;

            return $result;
        }

        $result = $channel->sendText($channelConfig, $to, $body, $options);
        $result['used_template'] = false;

        return $result;
    }

    /**
     * Force a template send regardless of window state.
     *
     * @param array<string, mixed> $channelConfig
     * @param array<string, mixed> $parameters
     * @param array<string, mixed> $options
     */
    public function sendTemplate(
        string $channelName,
        array $channelConfig,
        string $to,
        string $templateName,
        string $language = 'en_US',
        array $parameters = [],
        array $options = [],
    ): array {
        $channel = $this->registry->get($channelName);
        if (null === $channel) {
            return ['success' => false, 'error' => 'Unknown channel: ' . $channelName];
        }

        if (!$channel->supportsTemplates()) {
            return [
                'success' => false,
                'error'   => $channel->getDisplayName() . ' does not support templates',
            ];
        }

        $result = $channel->sendTemplate($channelConfig, $to, $templateName, $language, $parameters, $options);
        $result['used_template'] = true;

        return $result;
    }

    public function getRegistry(): ChannelRegistry
    {
        return $this->registry;
    }

    public function getWindowManager(): WindowManager
    {
        return $this->windowManager;
    }
}
