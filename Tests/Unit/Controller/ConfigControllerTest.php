<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Tests\Unit\Controller;

use MauticPlugin\MauticOmnichannelMessagingBundle\Controller\ConfigController;
use MauticPlugin\MauticOmnichannelMessagingBundle\Integration\OmnichannelIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class ConfigControllerTest extends TestCase
{
    private ConfigController $controller;
    private OmnichannelIntegration $integration;

    protected function setUp(): void
    {
        $this->integration = new OmnichannelIntegration();
        $this->controller = new ConfigController($this->integration);
    }

    public function testIndexListsChannels(): void
    {
        $response = $this->controller->indexAction();
        self::assertSame(200, $response->getStatusCode());
        $content = $response->getContent() ?: '';
        self::assertStringContainsString('whatsapp', $content);
        self::assertStringContainsString('telegram', $content);
    }

    public function testEditAndSaveWhatsApp(): void
    {
        $request = Request::create('/s/omnichannel/config/whatsapp', 'POST', [
            'enabled'         => '1',
            'phone_number_id' => 'pn-123',
            'access_token'    => 'tok-abc',
            'verify_token'    => 'verify-me',
        ]);

        $response = $this->controller->editAction($request, 'whatsapp');
        self::assertSame(302, $response->getStatusCode());
        self::assertTrue($this->integration->isChannelEnabled('whatsapp'));
        self::assertSame('pn-123', $this->integration->getChannelConfig('whatsapp')['phone_number_id']);
    }

    public function testEditUnknownChannel(): void
    {
        $response = $this->controller->editAction(Request::create('/'), 'unknown');
        self::assertSame(404, $response->getStatusCode());
    }
}
