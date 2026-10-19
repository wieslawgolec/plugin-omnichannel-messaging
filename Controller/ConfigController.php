<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Controller;

use MauticPlugin\MauticOmnichannelMessagingBundle\Integration\OmnichannelIntegration;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Per-channel credential configuration UI.
 */
final class ConfigController
{
    public function __construct(
        private readonly OmnichannelIntegration $integration = new OmnichannelIntegration(),
    ) {
    }

    public function indexAction(): Response
    {
        $channels = array_keys(OmnichannelIntegration::getFormFields());
        $links = '';
        foreach ($channels as $ch) {
            $enabled = $this->integration->isChannelEnabled($ch) ? '✓ enabled' : 'disabled';
            $links .= sprintf(
                '<li><a href="/s/omnichannel/config/%s">%s</a> — %s</li>',
                htmlspecialchars($ch),
                htmlspecialchars(ucfirst($ch)),
                $enabled
            );
        }

        $html = '<html><body>
          <h1>Omnichannel Channel Configuration</h1>
          <ul>' . $links . '</ul>
        </body></html>';

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public function editAction(Request $request, string $channel): Response
    {
        $fields = OmnichannelIntegration::getFormFields()[$channel] ?? null;
        if (null === $fields) {
            return new Response('Unknown channel', 404);
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $cfg = [];
            foreach ($fields as $field) {
                $key = $field['key'];
                if ('bool' === $field['type']) {
                    $cfg[$key] = isset($data[$key]);
                } else {
                    $cfg[$key] = (string) ($data[$key] ?? '');
                }
            }
            $this->integration->setChannelConfig($channel, $cfg);

            return new Response('', 302, ['Location' => '/s/omnichannel/config']);
        }

        $current = $this->integration->getChannelConfig($channel);
        $inputs = '';
        foreach ($fields as $field) {
            $key = $field['key'];
            $val = $current[$key] ?? '';
            if ('bool' === $field['type']) {
                $checked = !empty($val) ? 'checked' : '';
                $inputs .= sprintf(
                    '<p><label><input type="checkbox" name="%s" %s> %s</label></p>',
                    htmlspecialchars($key),
                    $checked,
                    htmlspecialchars($field['label'])
                );
            } else {
                $type = 'password' === $field['type'] ? 'password' : 'text';
                $inputs .= sprintf(
                    '<p><label>%s<br><input type="%s" name="%s" value="%s" class="form-control" style="width:400px"></label></p>',
                    htmlspecialchars($field['label']),
                    $type,
                    htmlspecialchars($key),
                    htmlspecialchars(is_string($val) ? $val : '')
                );
            }
        }

        $html = sprintf(
            '<html><body>
            <h1>Configure %s</h1>
            <form method="post" action="/s/omnichannel/config/%s">
              %s
              <p><button type="submit">Save</button> <a href="/s/omnichannel/config">Cancel</a></p>
            </form>
            </body></html>',
            htmlspecialchars(ucfirst($channel)),
            htmlspecialchars($channel),
            $inputs
        );

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public function getIntegration(): OmnichannelIntegration
    {
        return $this->integration;
    }
}
