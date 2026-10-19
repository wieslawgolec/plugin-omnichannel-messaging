<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Tests\Unit\Model;

use MauticPlugin\MauticOmnichannelMessagingBundle\Entity\MessageTemplate;
use MauticPlugin\MauticOmnichannelMessagingBundle\Model\TemplateModel;
use PHPUnit\Framework\TestCase;

final class TemplateModelTest extends TestCase
{
    private TemplateModel $model;

    protected function setUp(): void
    {
        $this->model = new TemplateModel();
    }

    public function testCreateSaveGetDelete(): void
    {
        $t = $this->model->createNew();
        $t->setName('order_confirm')
            ->setChannel('whatsapp')
            ->setStatus(MessageTemplate::STATUS_APPROVED)
            ->setBody('Your order {{1}} is confirmed');

        $saved = $this->model->save($t);
        self::assertNotNull($saved->getId());
        self::assertNotNull($saved->getDateApproved());

        $fetched = $this->model->getEntity($saved->getId());
        self::assertSame('order_confirm', $fetched?->getName());

        $this->model->deleteEntity($saved);
        self::assertNull($this->model->getEntity($saved->getId()));
    }

    public function testFilters(): void
    {
        $t1 = $this->model->createNew()->setName('a')->setChannel('whatsapp')->setStatus(MessageTemplate::STATUS_APPROVED);
        $t2 = $this->model->createNew()->setName('b')->setChannel('wechat')->setStatus(MessageTemplate::STATUS_DRAFT);
        $this->model->save($t1);
        $this->model->save($t2);

        $wa = $this->model->getEntities(['channel' => 'whatsapp']);
        self::assertCount(1, $wa);
        self::assertSame('a', $wa[0]->getName());

        $approved = $this->model->getApprovedForChannel('whatsapp');
        self::assertCount(1, $approved);

        $found = $this->model->findByNameAndChannel('a', 'whatsapp');
        self::assertNotNull($found);
        self::assertNull($this->model->findByNameAndChannel('missing', 'whatsapp'));
    }
}
