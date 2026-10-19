<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Tests\Unit\Model;

use MauticPlugin\MauticOmnichannelMessagingBundle\Entity\MessageLog;
use MauticPlugin\MauticOmnichannelMessagingBundle\Model\ConversationModel;
use PHPUnit\Framework\TestCase;

final class ConversationModelTest extends TestCase
{
    private ConversationModel $model;

    protected function setUp(): void
    {
        $this->model = new ConversationModel();
    }

    public function testFindOrCreateIsIdempotent(): void
    {
        $c1 = $this->model->findOrCreate('whatsapp', '15551112222', 10);
        $c2 = $this->model->findOrCreate('whatsapp', '15551112222', 10);

        self::assertSame($c1->getId(), $c2->getId());
        self::assertSame(10, $c1->getLeadId());
    }

    public function testRecordInboundOpensWindow(): void
    {
        $msg = $this->model->recordInbound(
            'whatsapp',
            '15551112222',
            'Hello from customer',
            'wamid.1',
            [],
            10
        );

        self::assertTrue($msg->isInbound());
        self::assertSame(MessageLog::STATUS_RECEIVED, $msg->getStatus());
        self::assertSame('Hello from customer', $msg->getBody());

        $conv = $this->model->getConversation($msg->getConversationId());
        self::assertNotNull($conv);
        self::assertTrue($conv->isWindowOpen());
        self::assertSame(1, $conv->getMessageCount());
        self::assertNotNull($conv->getWindowExpiresAt());
    }

    public function testRecordOutbound(): void
    {
        $conv = $this->model->findOrCreate('telegram', '987654', 5);
        $msg = $this->model->recordOutbound(
            $conv,
            'Reply text',
            true,
            'tg-99',
            null,
            false
        );

        self::assertTrue($msg->isOutbound());
        self::assertSame(MessageLog::STATUS_SENT, $msg->getStatus());
        self::assertSame('tg-99', $msg->getExternalMessageId());

        $failed = $this->model->recordOutbound($conv, 'fail', false, null, 'timeout');
        self::assertSame(MessageLog::STATUS_FAILED, $failed->getStatus());
        self::assertSame('timeout', $failed->getErrorMessage());

        $messages = $this->model->getMessagesForConversation((int) $conv->getId());
        self::assertCount(2, $messages);
    }

    public function testGetConversationsFilter(): void
    {
        $this->model->findOrCreate('whatsapp', '1');
        $this->model->findOrCreate('telegram', '2');
        $this->model->findOrCreate('whatsapp', '3');

        $wa = $this->model->getConversations(['channel' => 'whatsapp']);
        self::assertCount(2, $wa);
    }
}
