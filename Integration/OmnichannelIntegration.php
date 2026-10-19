<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Integration;

/**
 * Holds per-channel credentials and feature flags.
 * In full Mautic this would extend AbstractIntegration / use the IntegrationsBundle.
 */
class OmnichannelIntegration
{
    public const NAME = 'Omnichannel';

    /** @var array<string, array<string, mixed>> */
    private array $settings = [
        'whatsapp' => [
            'enabled'         => false,
            'phone_number_id' => '',
            'access_token'    => '',
            'verify_token'    => '',
            'waba_id'         => '',
        ],
        'telegram' => [
            'enabled'   => false,
            'bot_token' => '',
            'bot_username' => '',
        ],
        'wecom' => [
            'enabled'    => false,
            'corpid'     => '',
            'corpsecret' => '',
            'agentid'    => '',
        ],
        'wechat' => [
            'enabled'   => false,
            'appid'     => '',
            'appsecret' => '',
            'token'     => '',
        ],
        'discord' => [
            'enabled'            => false,
            'webhook_url'        => '',
            'bot_token'          => '',
            'default_channel_id' => '',
        ],
    ];

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDisplayName(): string
    {
        return 'Omnichannel Messaging';
    }

    /**
     * @return array<string, mixed>
     */
    public function getChannelConfig(string $channel): array
    {
        return $this->settings[$channel] ?? [];
    }

    /**
     * @param array<string, mixed> $config
     */
    public function setChannelConfig(string $channel, array $config): self
    {
        if (!isset($this->settings[$channel])) {
            return $this;
        }
        $this->settings[$channel] = array_merge($this->settings[$channel], $config);
        return $this;
    }

    public function isChannelEnabled(string $channel): bool
    {
        return (bool) ($this->settings[$channel]['enabled'] ?? false);
    }

    /**
     * @return list<string>
     */
    public function getEnabledChannels(): array
    {
        $enabled = [];
        foreach ($this->settings as $name => $cfg) {
            if (!empty($cfg['enabled'])) {
                $enabled[] = $name;
            }
        }
        return $enabled;
    }

    /**
     * Credentials needed by MessageSender / channel helpers.
     *
     * @return array<string, mixed>
     */
    public function getCredentialsForChannel(string $channel): array
    {
        $cfg = $this->getChannelConfig($channel);
        unset($cfg['enabled']);
        return $cfg;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getAllSettings(): array
    {
        return $this->settings;
    }

    /**
     * @param array<string, array<string, mixed>> $settings
     */
    public function setAllSettings(array $settings): self
    {
        foreach ($settings as $channel => $cfg) {
            if (isset($this->settings[$channel]) && is_array($cfg)) {
                $this->settings[$channel] = array_merge($this->settings[$channel], $cfg);
            }
        }
        return $this;
    }

    /**
     * Field definitions for the config form, per channel.
     *
     * @return array<string, list<array{key: string, label: string, type: string, required?: bool, tooltip?: string}>>
     */
    public static function getFormFields(): array
    {
        return [
            'whatsapp' => [
                ['key' => 'enabled', 'label' => 'Enabled', 'type' => 'bool'],
                ['key' => 'phone_number_id', 'label' => 'Phone Number ID', 'type' => 'text', 'required' => true],
                ['key' => 'access_token', 'label' => 'Access Token', 'type' => 'password', 'required' => true],
                ['key' => 'verify_token', 'label' => 'Webhook Verify Token', 'type' => 'text'],
                ['key' => 'waba_id', 'label' => 'WABA ID', 'type' => 'text'],
            ],
            'telegram' => [
                ['key' => 'enabled', 'label' => 'Enabled', 'type' => 'bool'],
                ['key' => 'bot_token', 'label' => 'Bot Token', 'type' => 'password', 'required' => true],
                ['key' => 'bot_username', 'label' => 'Bot Username', 'type' => 'text'],
            ],
            'wecom' => [
                ['key' => 'enabled', 'label' => 'Enabled', 'type' => 'bool'],
                ['key' => 'corpid', 'label' => 'Corp ID', 'type' => 'text', 'required' => true],
                ['key' => 'corpsecret', 'label' => 'Corp Secret', 'type' => 'password', 'required' => true],
                ['key' => 'agentid', 'label' => 'Agent ID', 'type' => 'text', 'required' => true],
            ],
            'wechat' => [
                ['key' => 'enabled', 'label' => 'Enabled', 'type' => 'bool'],
                ['key' => 'appid', 'label' => 'App ID', 'type' => 'text', 'required' => true],
                ['key' => 'appsecret', 'label' => 'App Secret', 'type' => 'password', 'required' => true],
                ['key' => 'token', 'label' => 'Token (webhook)', 'type' => 'text'],
            ],
            'discord' => [
                ['key' => 'enabled', 'label' => 'Enabled', 'type' => 'bool'],
                ['key' => 'webhook_url', 'label' => 'Webhook URL', 'type' => 'text'],
                ['key' => 'bot_token', 'label' => 'Bot Token', 'type' => 'password'],
                ['key' => 'default_channel_id', 'label' => 'Default Channel ID', 'type' => 'text'],
            ],
        ];
    }
}
