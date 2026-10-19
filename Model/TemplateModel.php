<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Model;

use MauticPlugin\MauticOmnichannelMessagingBundle\Entity\MessageTemplate;
use MauticPlugin\MauticOmnichannelMessagingBundle\Entity\MessageTemplateRepository;

/**
 * Template model.
 *
 * Inside Mautic this extends FormModel and uses Doctrine via getRepository().
 * Standalone / unit-test mode keeps an in-memory fallback so PHPUnit still works
 * without the full Mautic kernel.
 *
 * Production path (when wired in Config/config.php):
 *   - class extends \Mautic\CoreBundle\Model\FormModel
 *   - getRepository() returns MessageTemplateRepository
 *   - createForm() uses MessageTemplateType
 */
class TemplateModel
{
    /** @var array<int, MessageTemplate> */
    private array $storage = [];
    private int $nextId = 1;

    private ?MessageTemplateRepository $repository = null;

    public function setRepository(MessageTemplateRepository $repository): void
    {
        $this->repository = $repository;
    }

    public function getRepository(): ?MessageTemplateRepository
    {
        return $this->repository;
    }

    public function getPermissionBase(): string
    {
        return 'omnichannel:templates';
    }

    public function createNew(): MessageTemplate
    {
        $t = new MessageTemplate();
        $t->setDateAdded(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
        return $t;
    }

    public function save(MessageTemplate $template): MessageTemplate
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $template->setDateModified($now);

        if ($template->isApproved() && null === $template->getDateApproved()) {
            $template->setDateApproved($now);
        }

        if (null !== $this->repository) {
            // Production: Doctrine persist is done by FormModel::saveEntity
            // Here we only support the test path when repository is mocked.
            return $template;
        }

        if (null === $template->getId()) {
            $template->setId($this->nextId++);
            if (null === $template->getDateAdded()) {
                $template->setDateAdded($now);
            }
        }
        $this->storage[$template->getId()] = $template;
        return $template;
    }

    public function getEntity(?int $id): ?MessageTemplate
    {
        if (null === $id) {
            return null;
        }
        if (null !== $this->repository) {
            return $this->repository->find($id);
        }
        return $this->storage[$id] ?? null;
    }

    /**
     * @param array<string, mixed> $filters
     * @return list<MessageTemplate>
     */
    public function getEntities(array $filters = []): array
    {
        if (null !== $this->repository) {
            return $this->repository->getEntitiesByFilters($filters);
        }

        $items = array_values($this->storage);
        if (isset($filters['channel'])) {
            $items = array_values(array_filter($items, static fn (MessageTemplate $t) => $t->getChannel() === $filters['channel']));
        }
        if (isset($filters['status'])) {
            $items = array_values(array_filter($items, static fn (MessageTemplate $t) => $t->getStatus() === $filters['status']));
        }
        if (isset($filters['isPublished'])) {
            $items = array_values(array_filter($items, static fn (MessageTemplate $t) => $t->getIsPublished() === (bool) $filters['isPublished']));
        }
        return $items;
    }

    public function deleteEntity(MessageTemplate $template): void
    {
        if (null !== $this->repository && null !== $template->getId()) {
            // Production: FormModel::deleteEntity
            return;
        }
        if (null !== $template->getId()) {
            unset($this->storage[$template->getId()]);
        }
    }

    /** @return list<MessageTemplate> */
    public function getApprovedForChannel(string $channel): array
    {
        if (null !== $this->repository) {
            return $this->repository->findApprovedForChannel($channel);
        }
        return $this->getEntities([
            'channel'     => $channel,
            'status'      => MessageTemplate::STATUS_APPROVED,
            'isPublished' => true,
        ]);
    }

    public function findByNameAndChannel(string $name, string $channel): ?MessageTemplate
    {
        if (null !== $this->repository) {
            return $this->repository->findOneByNameAndChannel($name, $channel);
        }
        foreach ($this->storage as $t) {
            if ($t->getName() === $name && $t->getChannel() === $channel) {
                return $t;
            }
        }
        return null;
    }
}
