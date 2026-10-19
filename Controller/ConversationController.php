<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Controller;

use Symfony\Component\HttpFoundation\Response;

/**
 * Stub admin controller for conversation timeline UI.
 */
final class ConversationController
{
    public function indexAction(int $page = 1): Response
    {
        return new Response(
            '<html><body><h1>Omnichannel Conversations</h1><p>Conversation timeline UI (coming soon).</p></body></html>',
            200,
            ['Content-Type' => 'text/html']
        );
    }
}
