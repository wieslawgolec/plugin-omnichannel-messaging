<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Tests\Unit\Entity;

use MauticPlugin\MauticOmnichannelMessagingBundle\Entity\Conversation;
use PHPUnit\Framework\TestCase;

final class ConversationTest extends TestCase
{
    public function testOpenWindowSetsExpiry(): void
    {
        $c = new Conversation();
        $now = new \DateTimeImmutable('2026-01-01 12:00:00', new \DateTimeZone('UTC'));
        $c->openWindow(24, $now);

        self::assertTrue($c->isOpen());
        self::assertSame($now, $c->getLastInboundAt());
        self::assertNotNull($c->getWindowExpiresAt());
        self::assertSame(
            '2026-01-02 12:00:00',
            $c->getWindowExpiresAt()->format('Y-m-d H:i:s')
        );
        self::assertTrue($c->isWindowOpen($now));
        self::assertFalse($c->isWindowOpen($now->modify('+25 hours')));
    }

    public function testOpenWindowWithZeroHours(): void
    {
        $c = new Conversation();
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $c->openWindow(0, $now);

        self::assertNull($c->getWindowExpiresAt());
        self::assertTrue($c->isWindowOpen($now));
    }

    public function testIncrementMessageCount(): void
    {
        $c = new Conversation();
        self::assertSame(0, $c->getMessageCount());
        $c->incrementMessageCount()->incrementMessageCount();
        self::assertSame(2, $c->getMessageCount());
    }

    public function testFindOrCreateStyleFields(): void
    {
        $c = new Conversation();
        $c->setChannel('whatsapp')
            ->setContactIdentifier('15551234567')
            ->setLeadId(42)
            ->setSubject('Support');

        self::assertSame('whatsapp', $c->getChannel());
        self::assertSame('15551234567', $c->getContactIdentifier());
        self::assertSame(42, $c->getLeadId());
        self::assertSame('Support', $c->getSubject());
    }
}
