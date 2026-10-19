<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Controller;

use MauticPlugin\MauticOmnichannelMessagingBundle\Model\ConversationModel;
use Symfony\Component\HttpFoundation\Response;

final class ConversationController
{
    public function __construct(
        private readonly ConversationModel $model = new ConversationModel(),
    ) {
    }

    public function indexAction(int $page = 1): Response
    {
        $conversations = $this->model->getConversations();
        $rows = '';
        foreach ($conversations as $c) {
            $window = $c->isWindowOpen() ? 'open' : 'closed';
            $rows .= sprintf(
                '<tr><td>%d</td><td><a href="/s/omnichannel/conversations/view/%d">%s</a></td>
                <td>%s</td><td>%s</td><td>%s</td><td>%d</td><td>%s</td></tr>',
                $c->getId(), $c->getId(), htmlspecialchars($c->getContactIdentifier()),
                htmlspecialchars($c->getChannel()), htmlspecialchars($c->getStatus()),
                $window, $c->getMessageCount(),
                $c->getDateModified()?->format('Y-m-d H:i') ?? '—'
            );
        }
        $html = '<html><body><h1>Omnichannel Conversations</h1>
          <table border="1" cellpadding="6">
            <thead><tr><th>ID</th><th>Contact</th><th>Channel</th><th>Status</th><th>Window</th><th>Msgs</th><th>Updated</th></tr></thead>
            <tbody>' . $rows . '</tbody></table></body></html>';
        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public function viewAction(int $objectId): Response
    {
        $conversation = $this->model->getConversation($objectId);
        if (null === $conversation) {
            return new Response('Conversation not found', 404);
        }
        $messages = $this->model->getMessagesForConversation($objectId);
        $timeline = '';
        foreach ($messages as $m) {
            $dir = $m->isInbound() ? 'IN' : 'OUT';
            $extra = $m->getUsedTemplate() ? ' [template: ' . htmlspecialchars((string) $m->getTemplateName()) . ']' : '';
            if ($m->getErrorMessage()) {
                $extra .= ' Error: ' . htmlspecialchars($m->getErrorMessage());
            }
            $timeline .= sprintf(
                '<div style="margin:8px 0;padding:8px;border-left:4px solid %s"><strong>%s</strong> <small>%s</small> <em>%s</em>%s<br>%s</div>',
                $m->isInbound() ? '#2196F3' : '#4CAF50',
                $dir,
                $m->getDateAdded()?->format('Y-m-d H:i:s') ?? '',
                htmlspecialchars($m->getStatus()),
                $extra,
                htmlspecialchars($m->getBody())
            );
        }
        $windowInfo = $conversation->isWindowOpen()
            ? 'Window OPEN' . ($conversation->getWindowExpiresAt() ? ' until ' . $conversation->getWindowExpiresAt()->format('Y-m-d H:i') : '')
            : 'Window CLOSED';
        $html = sprintf(
            '<html><body><h1>Conversation #%d</h1>
            <p>Channel: <strong>%s</strong> | Contact: <strong>%s</strong> | Status: %s | %s | Messages: %d</p>
            <h2>Timeline</h2>%s
            <p><a href="/s/omnichannel/conversations">Back</a></p></body></html>',
            $conversation->getId(),
            htmlspecialchars($conversation->getChannel()),
            htmlspecialchars($conversation->getContactIdentifier()),
            htmlspecialchars($conversation->getStatus()),
            $windowInfo,
            $conversation->getMessageCount(),
            $timeline ?: '<p><em>No messages yet.</em></p>'
        );
        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public function getModel(): ConversationModel
    {
        return $this->model;
    }
}
