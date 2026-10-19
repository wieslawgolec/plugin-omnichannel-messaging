<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Tests\Unit\Helper;

use MauticPlugin\MauticOmnichannelMessagingBundle\Helper\MessengerChannelHelper;
use PHPUnit\Framework\TestCase;

final class MessengerChannelHelperTest extends TestCase
{
    private MessengerChannelHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new MessengerChannelHelper();
    }

    public function testMetadata(): void
    {
        self::assertSame('messenger', $this->helper->getName());
        self::assertSame('Facebook Messenger', $this->helper->getDisplayName());
        self::assertSame(24, $this->helper->getWindowHours());
        self::assertTrue($this->helper->supportsTemplates());
        self::assertTrue($this->helper->supportsInbound());
        self::assertFalse($this->helper->supportsFreeformOutsideWindow());
        self::assertTrue($this->helper->supportsMedia());
    }

    public function testSendTextRequiresToken(): void
    {
        $result = $this->helper->sendText([], '1234567890', 'Hello');
        self::assertFalse($result['success']);
        self::assertStringContainsString('page_access_token', $result['error'] ?? '');
    }

    public function testSendTemplateRequiresToken(): void
    {
        $result = $this->helper->sendTemplate([], '1234567890', 'HUMAN_AGENT', 'en_US', ['Hi']);
        self::assertFalse($result['success']);
        self::assertStringContainsString('page_access_token', $result['error'] ?? '');
    }

    public function testParseInboundValidMessage(): void
    {
        $payload = [
            'entry' => [[
                'messaging' => [[
                    'sender' => ['id' => 'psid-111'],
                    'message' => [
                        'mid'  => 'mid.abc',
                        'text' => 'Hello page',
                    ],
                ]],
            ]],
        ];

        $parsed = $this->helper->parseInbound($payload);

        self::assertTrue($parsed['valid']);
        self::assertSame('psid-111', $parsed['contact_identifier']);
        self::assertSame('Hello page', $parsed['body']);
        self::assertSame('mid.abc', $parsed['message_id']);
    }

    public function testParseInboundPostback(): void
    {
        $payload = [
            'entry' => [[
                'messaging' => [[
                    'sender' => ['id' => 'psid-222'],
                    'postback' => [
                        'title'   => 'Get Started',
                        'payload' => 'GET_STARTED',
                    ],
                ]],
            ]],
        ];

        $parsed = $this->helper->parseInbound($payload);

        self::assertTrue($parsed['valid']);
        self::assertSame('psid-222', $parsed['contact_identifier']);
        self::assertSame('Get Started', $parsed['body']);
    }

    public function testParseInboundWithAttachment(): void
    {
        $payload = [
            'entry' => [[
                'messaging' => [[
                    'sender' => ['id' => 'psid-333'],
                    'message' => [
                        'mid' => 'mid.img',
                        'attachments' => [[
                            'type' => 'image',
                            'payload' => ['url' => 'https://example.com/img.jpg'],
                        ]],
                    ],
                ]],
            ]],
        ];

        $parsed = $this->helper->parseInbound($payload);

        self::assertTrue($parsed['valid']);
        self::assertSame('image', $parsed['media']['type'] ?? null);
        self::assertSame('https://example.com/img.jpg', $parsed['media']['url'] ?? null);
    }

    public function testParseInboundInvalid(): void
    {
        self::assertFalse($this->helper->parseInbound([])['valid']);
        self::assertFalse($this->helper->parseInbound(['entry' => []])['valid']);
    }
}
