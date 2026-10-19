<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Entity;

/**
 * Conversation thread between a contact and a channel.
 *
 * Table: omnichannel_conversations
 */
class Conversation
{
    public const STATUS_OPEN   = 'open';
    public const STATUS_CLOSED = 'closed';

    private ?int $id = null;
    private ?int $leadId = null;
    private string $channel = '';
    private string $contactIdentifier = '';
    private string $status = self::STATUS_OPEN;
    private ?\DateTimeInterface $lastInboundAt = null;
    private ?\DateTimeInterface $lastOutboundAt = null;
    private ?\DateTimeInterface $windowExpiresAt = null;
    private int $messageCount = 0;
    private ?string $subject = null;
    private ?\DateTimeInterface $dateAdded = null;
    private ?\DateTimeInterface $dateModified = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getLeadId(): ?int
    {
        return $this->leadId;
    }

    public function setLeadId(?int $leadId): self
    {
        $this->leadId = $leadId;
        return $this;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function setChannel(string $channel): self
    {
        $this->channel = $channel;
        return $this;
    }

    public function getContactIdentifier(): string
    {
        return $this->contactIdentifier;
    }

    public function setContactIdentifier(string $identifier): self
    {
        $this->contactIdentifier = $identifier;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getLastInboundAt(): ?\DateTimeInterface
    {
        return $this->lastInboundAt;
    }

    public function setLastInboundAt(?\DateTimeInterface $date): self
    {
        $this->lastInboundAt = $date;
        return $this;
    }

    public function getLastOutboundAt(): ?\DateTimeInterface
    {
        return $this->lastOutboundAt;
    }

    public function setLastOutboundAt(?\DateTimeInterface $date): self
    {
        $this->lastOutboundAt = $date;
        return $this;
    }

    public function getWindowExpiresAt(): ?\DateTimeInterface
    {
        return $this->windowExpiresAt;
    }

    public function setWindowExpiresAt(?\DateTimeInterface $date): self
    {
        $this->windowExpiresAt = $date;
        return $this;
    }

    public function getMessageCount(): int
    {
        return $this->messageCount;
    }

    public function setMessageCount(int $count): self
    {
        $this->messageCount = $count;
        return $this;
    }

    public function incrementMessageCount(): self
    {
        ++$this->messageCount;
        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function getDateAdded(): ?\DateTimeInterface
    {
        return $this->dateAdded;
    }

    public function setDateAdded(?\DateTimeInterface $date): self
    {
        $this->dateAdded = $date;
        return $this;
    }

    public function getDateModified(): ?\DateTimeInterface
    {
        return $this->dateModified;
    }

    public function setDateModified(?\DateTimeInterface $date): self
    {
        $this->dateModified = $date;
        return $this;
    }

    public function isOpen(): bool
    {
        return self::STATUS_OPEN === $this->status;
    }

    /**
     * Refresh the session window from a new inbound message.
     */
    public function openWindow(int $windowHours, ?\DateTimeInterface $now = null): self
    {
        $now ??= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->lastInboundAt = $now;
        $this->status = self::STATUS_OPEN;

        if ($windowHours > 0) {
            $this->windowExpiresAt = \DateTimeImmutable::createFromInterface($now)
                ->modify('+' . $windowHours . ' hours');
        } else {
            $this->windowExpiresAt = null;
        }

        return $this;
    }

    public function isWindowOpen(?\DateTimeInterface $now = null): bool
    {
        if (null === $this->windowExpiresAt) {
            // no window restriction (Telegram, WeCom, Discord) or never opened
            return null === $this->lastInboundAt ? false : true;
        }

        $now ??= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        return $now <= $this->windowExpiresAt;
    }

    public static function loadMetadata(\Doctrine\ORM\Mapping\ClassMetadata $metadata): void
    {
        if (!class_exists(\Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder::class)) {
            return;
        }
        $builder = new \Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder($metadata);
        $builder->setTable('omnichannel_conversations')
            ->setCustomRepositoryClass(ConversationRepository::class)
            ->addIndex(['channel', 'contact_identifier'], 'omni_conv_channel_contact')
            ->addIndex(['lead_id'], 'omni_conv_lead')
            ->addIndex(['status'], 'omni_conv_status');
        $builder->addId();
        $builder->addNullableField('leadId', \Doctrine\DBAL\Types\Types::INTEGER, 'lead_id');
        $builder->addField('channel', \Doctrine\DBAL\Types\Types::STRING);
        $builder->addField('contactIdentifier', \Doctrine\DBAL\Types\Types::STRING, ['columnName' => 'contact_identifier', 'length' => 191]);
        $builder->addField('status', \Doctrine\DBAL\Types\Types::STRING, ['length' => 32]);
        $builder->addNullableField('lastInboundAt', \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, 'last_inbound_at');
        $builder->addNullableField('lastOutboundAt', \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, 'last_outbound_at');
        $builder->addNullableField('windowExpiresAt', \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, 'window_expires_at');
        $builder->addField('messageCount', \Doctrine\DBAL\Types\Types::INTEGER, ['columnName' => 'message_count']);
        $builder->addNullableField('subject', \Doctrine\DBAL\Types\Types::STRING, 'subject');
        $builder->addNullableField('dateAdded', \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, 'date_added');
        $builder->addNullableField('dateModified', \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, 'date_modified');
    }

}
