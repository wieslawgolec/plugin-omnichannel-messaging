<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Tests\Unit\Entity;

use MauticPlugin\MauticOmnichannelMessagingBundle\Entity\MessageLog;
use PHPUnit\Framework\TestCase;

final class MessageLogTest extends TestCase
{
    public function testMarkSent(): void
    {
        $m = new MessageLog();
        $m->setBody('Hello')->setChannel('telegram');
        self::assertSame(MessageLog::STATUS_PENDING, $m->getStatus());

        $m->markSent('ext-99');
        self::assertSame(MessageLog::STATUS_SENT, $m->getStatus());
        self::assertSame('ext-99', $m->getExternalMessageId());
        self::assertNotNull($m->getDateSent());
        self::assertTrue($m->isOutbound());
    }

    public function testMarkFailed(): void
    {
        $m = new MessageLog();
        $m->markFailed('rate limited');
        self::assertSame(MessageLog::STATUS_FAILED, $m->getStatus());
        self::assertSame('rate limited', $m->getErrorMessage());
    }

    public function testInboundDirection(): void
    {
        $m = new MessageLog();
        $m->setDirection(MessageLog::DIRECTION_INBOUND)
            ->setStatus(MessageLog::STATUS_RECEIVED)
            ->setBody('Hi')
            ->setMedia(['type' => 'image']);

        self::assertTrue($m->isInbound());
        self::assertFalse($m->isOutbound());
        self::assertSame(['type' => 'image'], $m->getMedia());
    }
}
