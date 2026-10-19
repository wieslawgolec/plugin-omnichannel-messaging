<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Tests\Unit\Helper;

use MauticPlugin\MauticOmnichannelMessagingBundle\Helper\InstagramChannelHelper;
use PHPUnit\Framework\TestCase;

final class InstagramChannelHelperTest extends TestCase
{
    private InstagramChannelHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new InstagramChannelHelper();
    }

    public function testMetadata(): void
    {
        self::assertSame('instagram', $this->helper->getName());
        self::assertSame('Instagram Messaging', $this->helper->getDisplayName());
        self::assertSame(24, $this->helper->getWindowHours());
        self::assertTrue($this->helper->supportsTemplates());
        self::assertTrue($this->helper->supportsInbound());
        self::assertFalse($this->helper->supportsFreeformOutsideWindow());
        self::assertTrue($this->helper->supportsMedia());
    }

    public function testSendTextRequiresToken(): void
    {
        $result = $this->helper->sendText(['ig_user_id' => '123'], 'igsid-1', 'Hello');
        self::assertFalse($result['success']);
        self::assertStringContainsString('page_access_token', $result['error'] ?? '');
    }

    public function testSendTextRequiresIgUserId(): void
    {
        $result = $this->helper->sendText(['page_access_token' => 'tok'], 'igsid-1', 'Hello');
        self::assertFalse($result['success']);
        self::assertStringContainsString('ig_user_id', $result['error'] ?? '');
    }

    public function testSendTemplateRequiresCredentials(): void
    {
        $result = $this->helper->sendTemplate([], 'igsid-1', 'HUMAN_AGENT', 'en_US', ['Hi']);
        self::assertFalse($result['success']);
        self::assertStringContainsString('page_access_token', $result['error'] ?? '');
    }

    public function testParseInboundValidMessage(): void
    {
        $payload = [
            'object' => 'instagram',
            'entry' => [[
                'messaging' => [[
                    'sender' => ['id' => 'igsid-111'],
                    'message' => [
                        'mid'  => 'mid.ig.abc',
                        'text' => 'Hello from IG',
                    ],
                ]],
            ]],
        ];

        $parsed = $this->helper->parseInbound($payload);

        self::assertTrue($parsed['valid']);
        self::assertSame('igsid-111', $parsed['contact_identifier']);
        self::assertSame('Hello from IG', $parsed['body']);
        self::assertSame('mid.ig.abc', $parsed['message_id']);
    }

    public function testParseInboundPostback(): void
    {
        $payload = [
            'entry' => [[
                'messaging' => [[
                    'sender' => ['id' => 'igsid-222'],
                    'postback' => [
                        'title'   => 'Shop now',
                        'payload' => 'SHOP',
                    ],
                ]],
            ]],
        ];

        $parsed = $this->helper->parseInbound($payload);

        self::assertTrue($parsed['valid']);
        self::assertSame('igsid-222', $parsed['contact_identifier']);
        self::assertSame('Shop now', $parsed['body']);
    }

    public function testParseInboundAttachment(): void
    {
        $payload = [
            'entry' => [[
                'messaging' => [[
                    'sender' => ['id' => 'igsid-333'],
                    'message' => [
                        'mid' => 'mid.img',
                        'attachments' => [[
                            'type' => 'image',
                            'payload' => ['url' => 'https://example.com/ig.jpg'],
                        ]],
                    ],
                ]],
            ]],
        ];

        $parsed = $this->helper->parseInbound($payload);

        self::assertTrue($parsed['valid']);
        self::assertSame('image', $parsed['media']['type'] ?? null);
        self::assertSame('https://example.com/ig.jpg', $parsed['media']['url'] ?? null);
    }

    public function testParseInboundStoryReply(): void
    {
        $payload = [
            'entry' => [[
                'messaging' => [[
                    'sender' => ['id' => 'igsid-444'],
                    'message' => [
                        'mid' => 'mid.story',
                        'reply_to' => [
                            'story' => ['url' => 'https://example.com/story.jpg'],
                        ],
                    ],
                ]],
            ]],
        ];

        $parsed = $this->helper->parseInbound($payload);

        self::assertTrue($parsed['valid']);
        self::assertSame('[story reply]', $parsed['body']);
        self::assertSame('story', $parsed['media']['type'] ?? null);
    }

    public function testParseInboundInvalid(): void
    {
        self::assertFalse($this->helper->parseInbound([])['valid']);
        self::assertFalse($this->helper->parseInbound(['entry' => []])['valid']);
    }
}
