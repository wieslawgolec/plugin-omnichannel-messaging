<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Controller;

use MauticPlugin\MauticOmnichannelMessagingBundle\Helper\ChannelRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public endpoint that receives inbound webhooks from messaging providers.
 *
 * Route: /omnichannel/webhook/{channel}
 */
final class WebhookController
{
    public function __construct(
        private readonly ChannelRegistry $registry,
    ) {
    }

    public function handleAction(Request $request, string $channel): Response
    {
        $helper = $this->registry->get($channel);
        if (null === $helper || !$helper->supportsInbound()) {
            return new Response('Channel not supported', 404);
        }

        // WhatsApp / Meta style verification challenge
        if ($request->isMethod('GET')) {
            $mode      = $request->query->get('hub_mode');
            $token     = $request->query->get('hub_verify_token');
            $challenge = $request->query->get('hub_challenge');

            // In production the expected token would come from plugin config
            if ('subscribe' === $mode && is_string($challenge)) {
                return new Response($challenge, 200, ['Content-Type' => 'text/plain']);
            }

            return new Response('Forbidden', 403);
        }

        $payload = [];
        $content = $request->getContent();
        if ('' !== $content) {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        // Fallback to request parameters
        if ([] === $payload) {
            $payload = $request->request->all();
        }

        $parsed = $helper->parseInbound($payload);

        if (!($parsed['valid'] ?? false)) {
            return new Response('Ignored', 200);
        }

        // In a full implementation we would:
        // 1. Resolve or create the Contact by contact_identifier
        // 2. Open / refresh the session window
        // 3. Persist the inbound message
        // 4. Dispatch OmnichannelEvents::MESSAGE_INBOUND
        // 5. Optionally trigger campaigns / add to segments

        return new Response('OK', 200);
    }
}
