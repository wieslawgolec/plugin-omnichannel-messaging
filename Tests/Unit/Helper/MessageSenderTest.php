<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Tests\Unit\Helper;

use MauticPlugin\MauticOmnichannelMessagingBundle\Helper\ChannelRegistry;
use MauticPlugin\MauticOmnichannelMessagingBundle\Helper\MessageSender;
use MauticPlugin\MauticOmnichannelMessagingBundle\Helper\WindowManager;
use PHPUnit\Framework\TestCase;

final class MessageSenderTest extends TestCase
{
    private MessageSender $sender;

    protected function setUp(): void
    {
        $this->sender = new MessageSender(new ChannelRegistry(), new WindowManager());
    }

    public function testUnknownChannel(): void
    {
        $result = $this->sender->send('unknown', [], 'to', 'body');
        self::assertFalse($result['success']);
        self::assertStringContainsString('Unknown channel', $result['error'] ?? '');
    }

    public function testWhatsAppRequiresTemplateWhenWindowClosed(): void
    {
        $last = new \DateTimeImmutable('-30 hours', new \DateTimeZone('UTC'));

        $result = $this->sender->send(
            'whatsapp',
            ['phone_number_id' => '123', 'access_token' => 'tok'],
            '15551234567',
            'Hello',
            $last
        );

        self::assertFalse($result['success']);
        self::assertStringContainsString('template_name', $result['error'] ?? '');
    }

    public function testTelegramAlwaysAllowsFreeform(): void
    {
        // No credentials → fails on missing token, but does NOT complain about window
        $last = new \DateTimeImmutable('-30 days', new \DateTimeZone('UTC'));

        $result = $this->sender->send(
            'telegram',
            [],
            '12345',
            'Hello',
            $last
        );

        self::assertFalse($result['success']);
        self::assertStringContainsString('bot_token', $result['error'] ?? '');
        self::assertFalse($result['used_template'] ?? true); // never reached template path
    }

    public function testForceTemplateOnUnsupportedChannel(): void
    {
        $result = $this->sender->sendTemplate(
            'telegram',
            [],
            '12345',
            'some_template'
        );

        self::assertFalse($result['success']);
        self::assertStringContainsString('does not support templates', $result['error'] ?? '');
    }

    public function testWhatsAppOpenWindowDoesNotRequireTemplate(): void
    {
        $last = new \DateTimeImmutable('-1 hour', new \DateTimeZone('UTC'));

        // Missing credentials → error about credentials, not about template
        $result = $this->sender->send(
            'whatsapp',
            [],
            '15551234567',
            'Hello',
            $last
        );

        self::assertFalse($result['success']);
        self::assertStringContainsString('Missing', $result['error'] ?? '');
        self::assertFalse($result['used_template'] ?? true);
    }

    public function testRegistryAccessible(): void
    {
        self::assertInstanceOf(ChannelRegistry::class, $this->sender->getRegistry());
        self::assertInstanceOf(WindowManager::class, $this->sender->getWindowManager());
    }
}
