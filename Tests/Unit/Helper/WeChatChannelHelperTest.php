<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Tests\Unit\Helper;

use MauticPlugin\MauticOmnichannelMessagingBundle\Helper\WeChatChannelHelper;
use PHPUnit\Framework\TestCase;

final class WeChatChannelHelperTest extends TestCase
{
    private WeChatChannelHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new WeChatChannelHelper();
    }

    public function testMetadata(): void
    {
        self::assertSame('wechat', $this->helper->getName());
        self::assertSame(48, $this->helper->getWindowHours());
        self::assertFalse($this->helper->supportsFreeformOutsideWindow());
        self::assertTrue($this->helper->supportsTemplates());
        self::assertTrue($this->helper->supportsInbound());
    }

    public function testSendTextWithoutCredentialsFails(): void
    {
        $result = $this->helper->sendText([], 'openid123', 'Hello');
        self::assertFalse($result['success']);
        self::assertStringContainsString('Missing', $result['error'] ?? '');
    }

    public function testParseInbound(): void
    {
        $payload = [
            'MsgType'      => 'text',
            'FromUserName' => 'oABC123',
            'Content'      => '你好公众号',
            'MsgId'        => '998877',
        ];

        $parsed = $this->helper->parseInbound($payload);

        self::assertTrue($parsed['valid']);
        self::assertSame('oABC123', $parsed['contact_identifier']);
        self::assertSame('你好公众号', $parsed['body']);
    }
}
