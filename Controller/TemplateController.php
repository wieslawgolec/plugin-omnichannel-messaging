<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Controller;

use MauticPlugin\MauticOmnichannelMessagingBundle\Entity\MessageTemplate;
use MauticPlugin\MauticOmnichannelMessagingBundle\Model\TemplateModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class TemplateController
{
    public function __construct(
        private readonly TemplateModel $model = new TemplateModel(),
    ) {
    }

    public function indexAction(int $page = 1): Response
    {
        $templates = $this->model->getEntities();
        return new Response($this->renderList($templates), 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public function newAction(Request $request): Response
    {
        return $this->handleForm($request, $this->model->createNew(), true);
    }

    public function editAction(Request $request, int $objectId): Response
    {
        $template = $this->model->getEntity($objectId);
        if (null === $template) {
            return new Response('Template not found', 404);
        }
        return $this->handleForm($request, $template, false);
    }

    public function viewAction(int $objectId): Response
    {
        $template = $this->model->getEntity($objectId);
        if (null === $template) {
            return new Response('Template not found', 404);
        }
        $html = sprintf(
            '<html><body><h1>Template: %s</h1><dl>
              <dt>Channel</dt><dd>%s</dd><dt>Language</dt><dd>%s</dd>
              <dt>Status</dt><dd>%s</dd><dt>Category</dt><dd>%s</dd>
              <dt>Published</dt><dd>%s</dd><dt>Body</dt><dd><pre>%s</pre></dd>
            </dl><p><a href="/s/omnichannel/templates">Back</a></p></body></html>',
            htmlspecialchars($template->getName()),
            htmlspecialchars($template->getChannel()),
            htmlspecialchars($template->getLanguage()),
            htmlspecialchars($template->getStatus()),
            htmlspecialchars($template->getCategory()),
            $template->getIsPublished() ? 'yes' : 'no',
            htmlspecialchars($template->getBody())
        );
        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public function deleteAction(int $objectId): Response
    {
        $template = $this->model->getEntity($objectId);
        if (null !== $template) {
            $this->model->deleteEntity($template);
        }
        return new Response('', 302, ['Location' => '/s/omnichannel/templates']);
    }

    private function handleForm(Request $request, MessageTemplate $template, bool $isNew): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $template->setName((string) ($data['name'] ?? $template->getName()));
            $template->setChannel((string) ($data['channel'] ?? $template->getChannel()));
            $template->setLanguage((string) ($data['language'] ?? $template->getLanguage()));
            $template->setCategory((string) ($data['category'] ?? $template->getCategory()));
            $template->setStatus((string) ($data['status'] ?? $template->getStatus()));
            $template->setBody((string) ($data['body'] ?? $template->getBody()));
            $template->setIsPublished(isset($data['isPublished']));
            $this->model->save($template);
            return new Response('', 302, ['Location' => '/s/omnichannel/templates/view/' . $template->getId()]);
        }
        return new Response($this->renderForm($template, $isNew), 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /** @param list<MessageTemplate> $templates */
    private function renderList(array $templates): string
    {
        $rows = '';
        foreach ($templates as $t) {
            $rows .= sprintf(
                '<tr><td>%d</td><td><a href="/s/omnichannel/templates/view/%d">%s</a></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td>
                <td><a href="/s/omnichannel/templates/edit/%d">Edit</a> | <a href="/s/omnichannel/templates/delete/%d">Delete</a></td></tr>',
                $t->getId(), $t->getId(), htmlspecialchars($t->getName()),
                htmlspecialchars($t->getChannel()), htmlspecialchars($t->getLanguage()),
                htmlspecialchars($t->getStatus()), $t->getIsPublished() ? 'Y' : 'N',
                $t->getId(), $t->getId()
            );
        }
        return '<html><body><h1>Omnichannel Templates</h1>
          <p><a href="/s/omnichannel/templates/new">+ New Template</a></p>
          <table border="1" cellpadding="6"><thead><tr><th>ID</th><th>Name</th><th>Channel</th><th>Lang</th><th>Status</th><th>Pub</th><th>Actions</th></tr></thead>
          <tbody>' . $rows . '</tbody></table></body></html>';
    }

    private function renderForm(MessageTemplate $t, bool $isNew): string
    {
        $title = $isNew ? 'New Template' : 'Edit Template #' . $t->getId();
        $action = $isNew ? '/s/omnichannel/templates/new' : '/s/omnichannel/templates/edit/' . $t->getId();
        return sprintf(
            '<html><body><h1>%s</h1><form method="post" action="%s">
              <p><label>Name <input name="name" value="%s" required></label></p>
              <p><label>Channel <select name="channel">
                <option value="whatsapp" %s>WhatsApp</option>
                <option value="wechat" %s>WeChat OA</option>
              </select></label></p>
              <p><label>Language <input name="language" value="%s"></label></p>
              <p><label>Category <select name="category">
                <option value="MARKETING" %s>Marketing</option>
                <option value="UTILITY" %s>Utility</option>
                <option value="AUTHENTICATION" %s>Authentication</option>
              </select></label></p>
              <p><label>Status <select name="status">
                <option value="draft" %s>draft</option>
                <option value="pending" %s>pending</option>
                <option value="approved" %s>approved</option>
                <option value="rejected" %s>rejected</option>
                <option value="disabled" %s>disabled</option>
              </select></label></p>
              <p><label>Body<br><textarea name="body" rows="6" cols="60">%s</textarea></label></p>
              <p><label><input type="checkbox" name="isPublished" %s> Published</label></p>
              <p><button type="submit">Save</button> <a href="/s/omnichannel/templates">Cancel</a></p>
            </form></body></html>',
            htmlspecialchars($title), htmlspecialchars($action), htmlspecialchars($t->getName()),
            'whatsapp' === $t->getChannel() ? 'selected' : '',
            'wechat' === $t->getChannel() ? 'selected' : '',
            htmlspecialchars($t->getLanguage()),
            'MARKETING' === $t->getCategory() ? 'selected' : '',
            'UTILITY' === $t->getCategory() ? 'selected' : '',
            'AUTHENTICATION' === $t->getCategory() ? 'selected' : '',
            'draft' === $t->getStatus() ? 'selected' : '',
            'pending' === $t->getStatus() ? 'selected' : '',
            'approved' === $t->getStatus() ? 'selected' : '',
            'rejected' === $t->getStatus() ? 'selected' : '',
            'disabled' === $t->getStatus() ? 'selected' : '',
            htmlspecialchars($t->getBody()),
            $t->getIsPublished() ? 'checked' : ''
        );
    }

    public function getModel(): TemplateModel
    {
        return $this->model;
    }
}
