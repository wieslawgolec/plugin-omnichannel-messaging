<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Tests\Unit\Helper;

use MauticPlugin\MauticOmnichannelMessagingBundle\Helper\TelegramChannelHelper;
use PHPUnit\Framework\TestCase;

final class TelegramChannelHelperTest extends TestCase
{
    private TelegramChannelHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new TelegramChannelHelper();
    }

    public function testMetadata(): void
    {
        self::assertSame('telegram', $this->helper->getName());
        self::assertSame(0, $this->helper->getWindowHours());
        self::assertTrue($this->helper->supportsFreeformOutsideWindow());
        self::assertFalse($this->helper->supportsTemplates());
        self::assertTrue($this->helper->supportsInbound());
    }

    public function testSendTextRequiresToken(): void
    {
        $result = $this->helper->sendText([], '12345', 'Hello');
        self::assertFalse($result['success']);
        self::assertStringContainsString('bot_token', $result['error'] ?? '');
    }

    public function testParseInboundValidMessage(): void
    {
        $payload = [
            'message' => [
                'message_id' => 42,
                'chat'       => ['id' => 987654321],
                'from'       => ['id' => 111, 'first_name' => 'Alice'],
                'text'       => 'Hello bot',
            ],
        ];

        $parsed = $this->helper->parseInbound($payload);

        self::assertTrue($parsed['valid']);
        self::assertSame('987654321', $parsed['contact_identifier']);
        self::assertSame('Hello bot', $parsed['body']);
        self::assertSame('42', $parsed['message_id']);
    }

    public function testParseInboundEditedMessage(): void
    {
        $payload = [
            'edited_message' => [
                'message_id' => 43,
                'chat'       => ['id' => 111],
                'text'       => 'Edited',
            ],
        ];

        $parsed = $this->helper->parseInbound($payload);
        self::assertTrue($parsed['valid']);
        self::assertSame('Edited', $parsed['body']);
    }

    public function testParseInboundInvalid(): void
    {
        self::assertFalse($this->helper->parseInbound([])['valid']);
        self::assertFalse($this->helper->parseInbound(['update_id' => 1])['valid']);
    }
}
