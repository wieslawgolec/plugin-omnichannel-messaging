<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Tests\Unit\Helper;

use MauticPlugin\MauticOmnichannelMessagingBundle\Helper\WhatsAppChannelHelper;
use PHPUnit\Framework\TestCase;

final class WhatsAppChannelHelperTest extends TestCase
{
    private WhatsAppChannelHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new WhatsAppChannelHelper();
    }

    public function testMetadata(): void
    {
        self::assertSame('whatsapp', $this->helper->getName());
        self::assertSame('WhatsApp', $this->helper->getDisplayName());
        self::assertSame(24, $this->helper->getWindowHours());
        self::assertTrue($this->helper->supportsTemplates());
        self::assertTrue($this->helper->supportsInbound());
        self::assertFalse($this->helper->supportsFreeformOutsideWindow());
    }

    public function testSendTextRequiresCredentials(): void
    {
        $result = $this->helper->sendText([], '+1234567890', 'Hello');
        self::assertFalse($result['success']);
        self::assertStringContainsString('Missing', $result['error'] ?? '');
    }

    public function testSendTemplateRequiresCredentials(): void
    {
        $result = $this->helper->sendTemplate([], '+1234567890', 'hello_world', 'en_US');
        self::assertFalse($result['success']);
        self::assertStringContainsString('Missing', $result['error'] ?? '');
    }

    public function testParseInboundValidPayload(): void
    {
        $payload = [
            'entry' => [[
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'messages' => [[
                            'from' => '15551234567',
                            'id'   => 'wamid.ABC123',
                            'type' => 'text',
                            'text' => ['body' => 'Hi there'],
                        ]],
                    ],
                ]],
            ]],
        ];

        $parsed = $this->helper->parseInbound($payload);

        self::assertTrue($parsed['valid']);
        self::assertSame('15551234567', $parsed['contact_identifier']);
        self::assertSame('Hi there', $parsed['body']);
        self::assertSame('wamid.ABC123', $parsed['message_id']);
    }

    public function testParseInboundInvalidPayload(): void
    {
        self::assertFalse($this->helper->parseInbound([])['valid']);
        self::assertFalse($this->helper->parseInbound(['entry' => []])['valid']);
    }

    public function testParseInboundWithMedia(): void
    {
        $payload = [
            'entry' => [[
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'messages' => [[
                            'from'  => '15551234567',
                            'id'    => 'wamid.IMG1',
                            'type'  => 'image',
                            'image' => [
                                'id'        => 'media-id-1',
                                'mime_type' => 'image/jpeg',
                                'caption'   => 'Look at this',
                            ],
                        ]],
                    ],
                ]],
            ]],
        ];

        $parsed = $this->helper->parseInbound($payload);

        self::assertTrue($parsed['valid']);
        self::assertSame('Look at this', $parsed['body']);
        self::assertSame('image', $parsed['media']['type'] ?? null);
        self::assertSame('media-id-1', $parsed['media']['id'] ?? null);
    }
}
