<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Tests\Unit\Helper;

use MauticPlugin\MauticOmnichannelMessagingBundle\Helper\DiscordChannelHelper;
use PHPUnit\Framework\TestCase;

final class DiscordChannelHelperTest extends TestCase
{
    private DiscordChannelHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new DiscordChannelHelper();
    }

    public function testMetadata(): void
    {
        self::assertSame('discord', $this->helper->getName());
        self::assertSame(0, $this->helper->getWindowHours());
        self::assertTrue($this->helper->supportsFreeformOutsideWindow());
        self::assertFalse($this->helper->supportsTemplates());
        self::assertFalse($this->helper->supportsInbound());
    }

    public function testSendRequiresConfig(): void
    {
        $result = $this->helper->sendText([], 'channel-id', 'Hello');
        self::assertFalse($result['success']);
        self::assertStringContainsString('Missing', $result['error'] ?? '');
    }
}
