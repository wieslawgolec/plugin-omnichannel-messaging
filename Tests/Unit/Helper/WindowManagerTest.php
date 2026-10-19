<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Tests\Unit\Helper;

use MauticPlugin\MauticOmnichannelMessagingBundle\Helper\ChannelInterface;
use MauticPlugin\MauticOmnichannelMessagingBundle\Helper\WindowManager;
use PHPUnit\Framework\TestCase;

final class WindowManagerTest extends TestCase
{
    private WindowManager $manager;

    protected function setUp(): void
    {
        $this->manager = new WindowManager();
    }

    public function testNoWindowAlwaysAllowed(): void
    {
        self::assertTrue($this->manager->isFreeformAllowed(null, 0));
        self::assertTrue($this->manager->isFreeformAllowed(new \DateTimeImmutable('-10 days'), 0));
    }

    public function testClosedWhenNoInbound(): void
    {
        self::assertFalse($this->manager->isFreeformAllowed(null, 24));
    }

    public function testOpenInsideWindow(): void
    {
        $last = new \DateTimeImmutable('-2 hours', new \DateTimeZone('UTC'));
        $now  = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        self::assertTrue($this->manager->isFreeformAllowed($last, 24, $now));
    }

    public function testClosedOutsideWindow(): void
    {
        $last = new \DateTimeImmutable('-25 hours', new \DateTimeZone('UTC'));
        $now  = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        self::assertFalse($this->manager->isFreeformAllowed($last, 24, $now));
    }

    public function testRemainingSeconds(): void
    {
        $last = new \DateTimeImmutable('-1 hour', new \DateTimeZone('UTC'));
        $now  = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $remaining = $this->manager->remainingSeconds($last, 24, $now);
        self::assertGreaterThan(22 * 3600, $remaining);
        self::assertLessThanOrEqual(23 * 3600, $remaining);
    }

    public function testRemainingZeroWhenClosed(): void
    {
        $last = new \DateTimeImmutable('-30 hours', new \DateTimeZone('UTC'));
        self::assertSame(0, $this->manager->remainingSeconds($last, 24));
    }

    public function testMustUseTemplateWhenWindowClosedAndChannelSupportsTemplates(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('supportsTemplates')->willReturn(true);
        $channel->method('supportsFreeformOutsideWindow')->willReturn(false);
        $channel->method('getWindowHours')->willReturn(24);

        $last = new \DateTimeImmutable('-30 hours', new \DateTimeZone('UTC'));

        self::assertTrue($this->manager->mustUseTemplate($last, $channel));
    }

    public function testMustNotUseTemplateWhenWindowOpen(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('supportsTemplates')->willReturn(true);
        $channel->method('supportsFreeformOutsideWindow')->willReturn(false);
        $channel->method('getWindowHours')->willReturn(24);

        $last = new \DateTimeImmutable('-1 hour', new \DateTimeZone('UTC'));

        self::assertFalse($this->manager->mustUseTemplate($last, $channel));
    }

    public function testMustNotUseTemplateWhenChannelDoesNotSupportTemplates(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('supportsTemplates')->willReturn(false);
        $channel->method('supportsFreeformOutsideWindow')->willReturn(false);
        $channel->method('getWindowHours')->willReturn(24);

        $last = new \DateTimeImmutable('-30 hours', new \DateTimeZone('UTC'));

        self::assertFalse($this->manager->mustUseTemplate($last, $channel));
    }
}
