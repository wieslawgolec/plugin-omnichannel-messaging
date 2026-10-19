<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Tests\Unit\EventListener;

use MauticPlugin\MauticOmnichannelMessagingBundle\EventListener\CampaignSubscriber;
use MauticPlugin\MauticOmnichannelMessagingBundle\Helper\ChannelRegistry;
use MauticPlugin\MauticOmnichannelMessagingBundle\Helper\MessageSender;
use MauticPlugin\MauticOmnichannelMessagingBundle\Helper\WindowManager;
use PHPUnit\Framework\TestCase;

final class CampaignSubscriberTest extends TestCase
{
    private CampaignSubscriber $subscriber;

    protected function setUp(): void
    {
        $sender = new MessageSender(new ChannelRegistry(), new WindowManager());
        $this->subscriber = new CampaignSubscriber($sender);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = CampaignSubscriber::getSubscribedEvents();
        self::assertArrayHasKey('mautic.campaign_on_build', $events);
    }

    public function testExecuteSendUnknownChannel(): void
    {
        $result = $this->subscriber->executeSend(
            ['channel' => 'nope', 'message' => 'hi'],
            [],
            'recipient'
        );

        self::assertFalse($result['success']);
        self::assertStringContainsString('Unknown channel', $result['error'] ?? '');
    }

    public function testExecuteSendWithOverride(): void
    {
        $result = $this->subscriber->executeSend(
            [
                'channel'     => 'telegram',
                'message'     => 'Hello',
                'to_override' => '99999',
            ],
            [], // no bot_token → expected failure
            'original'
        );

        self::assertFalse($result['success']);
        // Still fails on credentials, proving the path was exercised
        self::assertStringContainsString('bot_token', $result['error'] ?? '');
    }

    public function testExecuteSendWhatsAppClosedWindow(): void
    {
        $last = new \DateTimeImmutable('-48 hours', new \DateTimeZone('UTC'));

        $result = $this->subscriber->executeSend(
            [
                'channel' => 'whatsapp',
                'message' => 'Hello',
            ],
            ['phone_number_id' => 'x', 'access_token' => 'y'],
            '15551234567',
            $last
        );

        self::assertFalse($result['success']);
        self::assertStringContainsString('template_name', $result['error'] ?? '');
    }
}
