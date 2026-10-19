<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Tests\Unit\Helper;

use MauticPlugin\MauticOmnichannelMessagingBundle\Helper\TwitchChannelHelper;
use PHPUnit\Framework\TestCase;

final class TwitchChannelHelperTest extends TestCase
{
    private TwitchChannelHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new TwitchChannelHelper();
    }

    public function testMetadata(): void
    {
        self::assertSame('twitch', $this->helper->getName());
        self::assertSame('Twitch', $this->helper->getDisplayName());
        self::assertSame(0, $this->helper->getWindowHours());
        self::assertTrue($this->helper->supportsFreeformOutsideWindow());
        self::assertFalse($this->helper->supportsTemplates());
        self::assertTrue($this->helper->supportsInbound());
        self::assertFalse($this->helper->supportsMedia());
    }

    public function testSendTextRequiresCredentials(): void
    {
        $result = $this->helper->sendText([], '12345', 'Hello chat');
        self::assertFalse($result['success']);
        self::assertStringContainsString('access_token', $result['error'] ?? '');
    }

    public function testSendTextRequiresBroadcaster(): void
    {
        $result = $this->helper->sendText(
            ['access_token' => 'tok', 'client_id' => 'cid'],
            '',
            'Hello'
        );
        self::assertFalse($result['success']);
        self::assertStringContainsString('broadcaster_id', $result['error'] ?? '');
    }

    public function testParseInboundEventSub(): void
    {
        $payload = [
            'event' => [
                'chatter_user_id' => '998877',
                'message_id'      => 'msg-1',
                'message'         => ['text' => 'PogChamp'],
            ],
        ];

        $parsed = $this->helper->parseInbound($payload);

        self::assertTrue($parsed['valid']);
        self::assertSame('998877', $parsed['contact_identifier']);
        self::assertSame('PogChamp', $parsed['body']);
        self::assertSame('msg-1', $parsed['message_id']);
    }

    public function testParseInboundSimpleShape(): void
    {
        $payload = [
            'username' => 'viewer1',
            'message'  => 'Hello stream',
            'id'       => 'x1',
        ];

        $parsed = $this->helper->parseInbound($payload);

        self::assertTrue($parsed['valid']);
        self::assertSame('viewer1', $parsed['contact_identifier']);
        self::assertSame('Hello stream', $parsed['body']);
    }

    public function testParseInboundInvalid(): void
    {
        self::assertFalse($this->helper->parseInbound([])['valid']);
        self::assertFalse($this->helper->parseInbound(['event' => []])['valid']);
    }
}
