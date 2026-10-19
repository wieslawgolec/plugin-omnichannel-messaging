<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Tests\Unit\Entity;

use MauticPlugin\MauticOmnichannelMessagingBundle\Entity\MessageTemplate;
use PHPUnit\Framework\TestCase;

final class MessageTemplateTest extends TestCase
{
    public function testDefaultsAndFluentSetters(): void
    {
        $t = new MessageTemplate();
        self::assertNull($t->getId());
        self::assertSame('', $t->getName());
        self::assertSame('whatsapp', $t->getChannel());
        self::assertSame(MessageTemplate::STATUS_DRAFT, $t->getStatus());
        self::assertTrue($t->getIsPublished());
        self::assertFalse($t->isApproved());
        self::assertFalse($t->canBeSent());

        $t->setName('hello_world')
            ->setChannel('whatsapp')
            ->setLanguage('en_US')
            ->setCategory('UTILITY')
            ->setStatus(MessageTemplate::STATUS_APPROVED)
            ->setBody('Hello {{1}}')
            ->setParameters(['John'])
            ->setIsPublished(true)
            ->setExternalId('ext-1');

        self::assertSame('hello_world', $t->getName());
        self::assertTrue($t->isApproved());
        self::assertTrue($t->canBeSent());
        self::assertSame(['John'], $t->getParameters());
        self::assertSame('ext-1', $t->getExternalId());
    }

    public function testCanBeSentRequiresApprovedAndPublished(): void
    {
        $t = new MessageTemplate();
        $t->setStatus(MessageTemplate::STATUS_APPROVED)->setIsPublished(false);
        self::assertFalse($t->canBeSent());

        $t->setIsPublished(true);
        self::assertTrue($t->canBeSent());
    }

    public function testAvailableStatuses(): void
    {
        $statuses = MessageTemplate::getAvailableStatuses();
        self::assertContains('draft', $statuses);
        self::assertContains('approved', $statuses);
        self::assertCount(5, $statuses);
    }
}
