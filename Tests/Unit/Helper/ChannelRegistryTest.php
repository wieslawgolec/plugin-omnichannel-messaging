<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Tests\Unit\Helper;

use MauticPlugin\MauticOmnichannelMessagingBundle\Helper\ChannelRegistry;
use MauticPlugin\MauticOmnichannelMessagingBundle\Helper\DiscordChannelHelper;
use MauticPlugin\MauticOmnichannelMessagingBundle\Helper\TelegramChannelHelper;
use MauticPlugin\MauticOmnichannelMessagingBundle\Helper\WeChatChannelHelper;
use MauticPlugin\MauticOmnichannelMessagingBundle\Helper\WeComChannelHelper;
use MauticPlugin\MauticOmnichannelMessagingBundle\Helper\WhatsAppChannelHelper;
use PHPUnit\Framework\TestCase;

final class ChannelRegistryTest extends TestCase
{
    private ChannelRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new ChannelRegistry();
    }

    public function testAllExpectedChannelsAreRegistered(): void
    {
        $names = $this->registry->names();

        self::assertContains('whatsapp', $names);
        self::assertContains('telegram', $names);
        self::assertContains('wecom', $names);
        self::assertContains('wechat', $names);
        self::assertContains('discord', $names);
        self::assertCount(5, $names);
    }

    public function testGetReturnsCorrectInstance(): void
    {
        self::assertInstanceOf(WhatsAppChannelHelper::class, $this->registry->get('whatsapp'));
        self::assertInstanceOf(TelegramChannelHelper::class, $this->registry->get('telegram'));
        self::assertInstanceOf(WeComChannelHelper::class, $this->registry->get('wecom'));
        self::assertInstanceOf(WeChatChannelHelper::class, $this->registry->get('wechat'));
        self::assertInstanceOf(DiscordChannelHelper::class, $this->registry->get('discord'));
        self::assertNull($this->registry->get('nonexistent'));
    }

    public function testWithTemplates(): void
    {
        $withTemplates = $this->registry->withTemplates();
        $names = array_map(static fn ($c) => $c->getName(), $withTemplates);

        self::assertContains('whatsapp', $names);
        self::assertContains('wechat', $names);
        self::assertNotContains('telegram', $names);
        self::assertNotContains('discord', $names);
    }

    public function testWithInbound(): void
    {
        $withInbound = $this->registry->withInbound();
        $names = array_map(static fn ($c) => $c->getName(), $withInbound);

        self::assertContains('whatsapp', $names);
        self::assertContains('telegram', $names);
        self::assertContains('wecom', $names);
        self::assertContains('wechat', $names);
        self::assertNotContains('discord', $names);
    }

    public function testChannelCapabilities(): void
    {
        $wa = $this->registry->get('whatsapp');
        self::assertNotNull($wa);
        self::assertSame(24, $wa->getWindowHours());
        self::assertTrue($wa->supportsTemplates());
        self::assertFalse($wa->supportsFreeformOutsideWindow());
        self::assertTrue($wa->supportsInbound());

        $tg = $this->registry->get('telegram');
        self::assertNotNull($tg);
        self::assertSame(0, $tg->getWindowHours());
        self::assertFalse($tg->supportsTemplates());
        self::assertTrue($tg->supportsFreeformOutsideWindow());

        $wc = $this->registry->get('wechat');
        self::assertNotNull($wc);
        self::assertSame(48, $wc->getWindowHours());
        self::assertTrue($wc->supportsTemplates());
    }
}
