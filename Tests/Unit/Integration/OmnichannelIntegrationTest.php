<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Tests\Unit\Integration;

use MauticPlugin\MauticOmnichannelMessagingBundle\Integration\OmnichannelIntegration;
use PHPUnit\Framework\TestCase;

final class OmnichannelIntegrationTest extends TestCase
{
    private OmnichannelIntegration $integration;

    protected function setUp(): void
    {
        $this->integration = new OmnichannelIntegration();
    }

    public function testDefaultChannelsDisabled(): void
    {
        self::assertFalse($this->integration->isChannelEnabled('whatsapp'));
        self::assertSame([], $this->integration->getEnabledChannels());
    }

    public function testSetAndGetChannelConfig(): void
    {
        $this->integration->setChannelConfig('whatsapp', [
            'enabled'         => true,
            'phone_number_id' => '123456',
            'access_token'    => 'secret-token',
        ]);

        self::assertTrue($this->integration->isChannelEnabled('whatsapp'));
        self::assertSame(['whatsapp'], $this->integration->getEnabledChannels());

        $creds = $this->integration->getCredentialsForChannel('whatsapp');
        self::assertSame('123456', $creds['phone_number_id']);
        self::assertSame('secret-token', $creds['access_token']);
        self::assertArrayNotHasKey('enabled', $creds);
    }

    public function testFormFieldsCoverAllChannels(): void
    {
        $fields = OmnichannelIntegration::getFormFields();
        self::assertArrayHasKey('whatsapp', $fields);
        self::assertArrayHasKey('telegram', $fields);
        self::assertArrayHasKey('wecom', $fields);
        self::assertArrayHasKey('wechat', $fields);
        self::assertArrayHasKey('discord', $fields);
        self::assertArrayHasKey('messenger', $fields);
        self::assertArrayHasKey('twitch', $fields);
        self::assertArrayHasKey('instagram', $fields);

        $waKeys = array_column($fields['whatsapp'], 'key');
        self::assertContains('phone_number_id', $waKeys);
        self::assertContains('access_token', $waKeys);

        $msKeys = array_column($fields['messenger'], 'key');
        self::assertContains('page_access_token', $msKeys);

        $twKeys = array_column($fields['twitch'], 'key');
        self::assertContains('access_token', $twKeys);
        self::assertContains('client_id', $twKeys);

        $igKeys = array_column($fields['instagram'], 'key');
        self::assertContains('ig_user_id', $igKeys);
        self::assertContains('page_access_token', $igKeys);
    }

    public function testSetAllSettings(): void
    {
        $this->integration->setAllSettings([
            'telegram' => ['enabled' => true, 'bot_token' => 'tok'],
            'discord'  => ['enabled' => true, 'webhook_url' => 'https://discord.com/api/webhooks/x'],
        ]);

        self::assertTrue($this->integration->isChannelEnabled('telegram'));
        self::assertTrue($this->integration->isChannelEnabled('discord'));
        self::assertCount(2, $this->integration->getEnabledChannels());
    }
}
