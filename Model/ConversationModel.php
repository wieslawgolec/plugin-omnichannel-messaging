<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Model;

use MauticPlugin\MauticOmnichannelMessagingBundle\Entity\Conversation;
use MauticPlugin\MauticOmnichannelMessagingBundle\Entity\ConversationRepository;
use MauticPlugin\MauticOmnichannelMessagingBundle\Entity\MessageLog;
use MauticPlugin\MauticOmnichannelMessagingBundle\Entity\MessageLogRepository;
use MauticPlugin\MauticOmnichannelMessagingBundle\Helper\ChannelRegistry;
use MauticPlugin\MauticOmnichannelMessagingBundle\Helper\WindowManager;

/**
 * Conversation + message log model.
 *
 * Production: inject ConversationRepository + MessageLogRepository via DI.
 * Tests: in-memory fallback.
 */
class ConversationModel
{
    /** @var array<int, Conversation> */
    private array $conversations = [];
    /** @var array<int, MessageLog> */
    private array $messages = [];
    private int $nextConvId = 1;
    private int $nextMsgId = 1;

    private ?ConversationRepository $conversationRepository = null;
    private ?MessageLogRepository $messageLogRepository = null;

    public function __construct(
        private readonly ChannelRegistry $registry = new ChannelRegistry(),
        private readonly WindowManager $windowManager = new WindowManager(),
    ) {
    }

    public function setConversationRepository(ConversationRepository $repo): void
    {
        $this->conversationRepository = $repo;
    }

    public function setMessageLogRepository(MessageLogRepository $repo): void
    {
        $this->messageLogRepository = $repo;
    }

    public function getPermissionBase(): string
    {
        return 'omnichannel:conversations';
    }

    public function createNew(): Conversation
    {
        $c = new Conversation();
        $c->setDateAdded(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
        return $c;
    }

    public function saveConversation(Conversation $conversation): Conversation
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $conversation->setDateModified($now);

        if (null !== $this->conversationRepository) {
            return $conversation; // Doctrine flush handled by caller / FormModel
        }

        if (null === $conversation->getId()) {
            $conversation->setId($this->nextConvId++);
            if (null === $conversation->getDateAdded()) {
                $conversation->setDateAdded($now);
            }
        }
        $this->conversations[$conversation->getId()] = $conversation;
        return $conversation;
    }

    public function getConversation(?int $id): ?Conversation
    {
        if (null === $id) {
            return null;
        }
        if (null !== $this->conversationRepository) {
            return $this->conversationRepository->find($id);
        }
        return $this->conversations[$id] ?? null;
    }

    public function findOrCreate(string $channel, string $contactIdentifier, ?int $leadId = null): Conversation
    {
        if (null !== $this->conversationRepository) {
            $existing = $this->conversationRepository->findOneByChannelAndIdentifier($channel, $contactIdentifier);
            if (null !== $existing) {
                return $existing;
            }
        } else {
            foreach ($this->conversations as $c) {
                if ($c->getChannel() === $channel && $c->getContactIdentifier() === $contactIdentifier) {
                    return $c;
                }
            }
        }

        $c = $this->createNew();
        $c->setChannel($channel)->setContactIdentifier($contactIdentifier)->setLeadId($leadId);
        return $this->saveConversation($c);
    }

    /**
     * @param array<string, mixed> $filters
     * @return list<Conversation>
     */
    public function getConversations(array $filters = []): array
    {
        if (null !== $this->conversationRepository) {
            return $this->conversationRepository->getConversations($filters);
        }

        $items = array_values($this->conversations);
        if (isset($filters['channel'])) {
            $items = array_values(array_filter($items, static fn (Conversation $c) => $c->getChannel() === $filters['channel']));
        }
        if (isset($filters['status'])) {
            $items = array_values(array_filter($items, static fn (Conversation $c) => $c->getStatus() === $filters['status']));
        }
        if (isset($filters['leadId'])) {
            $items = array_values(array_filter($items, static fn (Conversation $c) => $c->getLeadId() === (int) $filters['leadId']));
        }
        usort($items, static fn (Conversation $a, Conversation $b) =>
            ($b->getDateModified()?->getTimestamp() ?? 0) <=> ($a->getDateModified()?->getTimestamp() ?? 0)
        );
        return $items;
    }

    public function recordInbound(
        string $channel,
        string $contactIdentifier,
        string $body,
        ?string $externalMessageId = null,
        array $media = [],
        ?int $leadId = null,
    ): MessageLog {
        $channelHelper = $this->registry->get($channel);
        $windowHours   = $channelHelper?->getWindowHours() ?? 0;

        $conversation = $this->findOrCreate($channel, $contactIdentifier, $leadId);
        $conversation->openWindow($windowHours);
        $conversation->incrementMessageCount();
        $this->saveConversation($conversation);

        $msg = new MessageLog();
        $msg->setConversationId($conversation->getId())
            ->setLeadId($leadId ?? $conversation->getLeadId())
            ->setChannel($channel)
            ->setDirection(MessageLog::DIRECTION_INBOUND)
            ->setStatus(MessageLog::STATUS_RECEIVED)
            ->setBody($body)
            ->setExternalMessageId($externalMessageId)
            ->setMedia($media)
            ->setDateAdded(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));

        return $this->persistMessage($msg);
    }

    public function recordOutbound(
        Conversation $conversation,
        string $body,
        bool $success,
        ?string $externalMessageId = null,
        ?string $error = null,
        bool $usedTemplate = false,
        ?string $templateName = null,
    ): MessageLog {
        $conversation->setLastOutboundAt(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
        $conversation->incrementMessageCount();
        $this->saveConversation($conversation);

        $msg = new MessageLog();
        $msg->setConversationId($conversation->getId())
            ->setLeadId($conversation->getLeadId())
            ->setChannel($conversation->getChannel())
            ->setDirection(MessageLog::DIRECTION_OUTBOUND)
            ->setBody($body)
            ->setUsedTemplate($usedTemplate)
            ->setTemplateName($templateName)
            ->setDateAdded(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));

        if ($success) {
            $msg->markSent($externalMessageId);
        } else {
            $msg->markFailed($error ?? 'Unknown error');
        }

        return $this->persistMessage($msg);
    }

    private function persistMessage(MessageLog $msg): MessageLog
    {
        if (null !== $this->messageLogRepository) {
            return $msg;
        }
        if (null === $msg->getId()) {
            $msg->setId($this->nextMsgId++);
        }
        $this->messages[$msg->getId()] = $msg;
        return $msg;
    }

    /** @return list<MessageLog> */
    public function getMessagesForConversation(int $conversationId): array
    {
        if (null !== $this->messageLogRepository) {
            return $this->messageLogRepository->findByConversation($conversationId);
        }
        $items = array_values(array_filter(
            $this->messages,
            static fn (MessageLog $m) => $m->getConversationId() === $conversationId
        ));
        usort($items, static fn (MessageLog $a, MessageLog $b) =>
            ($a->getDateAdded()?->getTimestamp() ?? 0) <=> ($b->getDateAdded()?->getTimestamp() ?? 0)
        );
        return $items;
    }

    public function getMessage(?int $id): ?MessageLog
    {
        if (null === $id) {
            return null;
        }
        if (null !== $this->messageLogRepository) {
            return $this->messageLogRepository->find($id);
        }
        return $this->messages[$id] ?? null;
    }
}
