<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Tests\Unit\Controller;

use MauticPlugin\MauticOmnichannelMessagingBundle\Controller\ConversationController;
use MauticPlugin\MauticOmnichannelMessagingBundle\Model\ConversationModel;
use PHPUnit\Framework\TestCase;

final class ConversationControllerTest extends TestCase
{
    private ConversationController $controller;
    private ConversationModel $model;

    protected function setUp(): void
    {
        $this->model = new ConversationModel();
        $this->controller = new ConversationController($this->model);
    }

    public function testIndexAndViewWithMessages(): void
    {
        $this->model->recordInbound('whatsapp', '15550001111', 'Hi there', 'wamid.x');
        $conv = $this->model->findOrCreate('whatsapp', '15550001111');
        $this->model->recordOutbound($conv, 'Thanks for writing', true, 'out-1');

        $list = $this->controller->indexAction();
        self::assertSame(200, $list->getStatusCode());
        self::assertStringContainsString('15550001111', $list->getContent() ?: '');

        $view = $this->controller->viewAction((int) $conv->getId());
        self::assertSame(200, $view->getStatusCode());
        $content = $view->getContent() ?: '';
        self::assertStringContainsString('Hi there', $content);
        self::assertStringContainsString('Thanks for writing', $content);
        self::assertStringContainsString('Window OPEN', $content);
    }

    public function testViewNotFound(): void
    {
        $response = $this->controller->viewAction(99999);
        self::assertSame(404, $response->getStatusCode());
    }
}
