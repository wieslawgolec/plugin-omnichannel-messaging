<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Tests\Unit\Controller;

use MauticPlugin\MauticOmnichannelMessagingBundle\Controller\TemplateController;
use MauticPlugin\MauticOmnichannelMessagingBundle\Entity\MessageTemplate;
use MauticPlugin\MauticOmnichannelMessagingBundle\Model\TemplateModel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class TemplateControllerTest extends TestCase
{
    private TemplateController $controller;
    private TemplateModel $model;

    protected function setUp(): void
    {
        $this->model = new TemplateModel();
        $this->controller = new TemplateController($this->model);
    }

    public function testIndexEmpty(): void
    {
        $response = $this->controller->indexAction();
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Omnichannel Templates', $response->getContent() ?: '');
    }

    public function testCreateAndView(): void
    {
        $request = Request::create('/s/omnichannel/templates/new', 'POST', [
            'name'        => 'welcome',
            'channel'     => 'whatsapp',
            'language'    => 'en_US',
            'category'    => 'MARKETING',
            'status'      => MessageTemplate::STATUS_APPROVED,
            'body'        => 'Welcome {{1}}',
            'isPublished' => '1',
        ]);

        $response = $this->controller->newAction($request);
        self::assertSame(302, $response->getStatusCode());

        $entities = $this->model->getEntities();
        self::assertCount(1, $entities);
        self::assertSame('welcome', $entities[0]->getName());
        self::assertTrue($entities[0]->canBeSent());

        $view = $this->controller->viewAction((int) $entities[0]->getId());
        self::assertSame(200, $view->getStatusCode());
        self::assertStringContainsString('welcome', $view->getContent() ?: '');
    }

    public function testViewNotFound(): void
    {
        $response = $this->controller->viewAction(999);
        self::assertSame(404, $response->getStatusCode());
    }

    public function testDelete(): void
    {
        $t = $this->model->createNew()->setName('tmp');
        $this->model->save($t);
        $id = (int) $t->getId();

        $response = $this->controller->deleteAction($id);
        self::assertSame(302, $response->getStatusCode());
        self::assertNull($this->model->getEntity($id));
    }
}
