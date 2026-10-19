<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Tests\Unit\Helper;

use MauticPlugin\MauticOmnichannelMessagingBundle\Helper\WeComChannelHelper;
use PHPUnit\Framework\TestCase;

final class WeComChannelHelperTest extends TestCase
{
    private WeComChannelHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new WeComChannelHelper();
    }

    public function testMetadata(): void
    {
        self::assertSame('wecom', $this->helper->getName());
        self::assertSame('WeCom', $this->helper->getDisplayName());
        self::assertSame(0, $this->helper->getWindowHours());
        self::assertTrue($this->helper->supportsFreeformOutsideWindow());
        self::assertFalse($this->helper->supportsTemplates());
        self::assertTrue($this->helper->supportsInbound());
    }

    public function testSendRequiresCredentials(): void
    {
        $result = $this->helper->sendText([], 'userid1', 'Hello');
        self::assertFalse($result['success']);
        self::assertStringContainsString('Missing', $result['error'] ?? '');
    }

    public function testParseInbound(): void
    {
        $payload = [
            'MsgType'      => 'text',
            'FromUserName' => 'ZhangSan',
            'Content'      => '你好',
            'MsgId'        => '123456789',
        ];

        $parsed = $this->helper->parseInbound($payload);

        self::assertTrue($parsed['valid']);
        self::assertSame('ZhangSan', $parsed['contact_identifier']);
        self::assertSame('你好', $parsed['body']);
        self::assertSame('123456789', $parsed['message_id']);
    }

    public function testParseInboundInvalid(): void
    {
        self::assertFalse($this->helper->parseInbound([])['valid']);
    }
}
