<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Controller;

use Symfony\Component\HttpFoundation\Response;

/**
 * Stub admin controller for template management UI.
 * Full CRUD would be wired once Doctrine entities are present.
 */
final class TemplateController
{
    public function indexAction(int $page = 1): Response
    {
        return new Response(
            '<html><body><h1>Omnichannel Templates</h1><p>Template management UI (coming soon).</p></body></html>',
            200,
            ['Content-Type' => 'text/html']
        );
    }
}
