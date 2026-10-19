<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Entity;

/**
 * Individual message (inbound or outbound) belonging to a conversation.
 *
 * Table: omnichannel_message_log
 */
class MessageLog
{
    public const DIRECTION_INBOUND  = 'inbound';
    public const DIRECTION_OUTBOUND = 'outbound';

    public const STATUS_PENDING   = 'pending';
    public const STATUS_SENT      = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_READ      = 'read';
    public const STATUS_FAILED    = 'failed';
    public const STATUS_RECEIVED  = 'received';

    private ?int $id = null;
    private ?int $conversationId = null;
    private ?int $leadId = null;
    private string $channel = '';
    private string $direction = self::DIRECTION_OUTBOUND;
    private string $status = self::STATUS_PENDING;
    private string $body = '';
    private ?string $externalMessageId = null;
    private ?string $templateName = null;
    private bool $usedTemplate = false;
    private ?string $errorMessage = null;
    /** @var array<string, mixed> */
    private array $media = [];
    /** @var array<string, mixed> */
    private array $rawPayload = [];
    private ?\DateTimeInterface $dateAdded = null;
    private ?\DateTimeInterface $dateSent = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getConversationId(): ?int
    {
        return $this->conversationId;
    }

    public function setConversationId(?int $id): self
    {
        $this->conversationId = $id;
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

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function setDirection(string $direction): self
    {
        $this->direction = $direction;
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

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function getExternalMessageId(): ?string
    {
        return $this->externalMessageId;
    }

    public function setExternalMessageId(?string $id): self
    {
        $this->externalMessageId = $id;
        return $this;
    }

    public function getTemplateName(): ?string
    {
        return $this->templateName;
    }

    public function setTemplateName(?string $name): self
    {
        $this->templateName = $name;
        return $this;
    }

    public function getUsedTemplate(): bool
    {
        return $this->usedTemplate;
    }

    public function setUsedTemplate(bool $used): self
    {
        $this->usedTemplate = $used;
        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $error): self
    {
        $this->errorMessage = $error;
        return $this;
    }

    /** @return array<string, mixed> */
    public function getMedia(): array
    {
        return $this->media;
    }

    /** @param array<string, mixed> $media */
    public function setMedia(array $media): self
    {
        $this->media = $media;
        return $this;
    }

    /** @return array<string, mixed> */
    public function getRawPayload(): array
    {
        return $this->rawPayload;
    }

    /** @param array<string, mixed> $payload */
    public function setRawPayload(array $payload): self
    {
        $this->rawPayload = $payload;
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

    public function getDateSent(): ?\DateTimeInterface
    {
        return $this->dateSent;
    }

    public function setDateSent(?\DateTimeInterface $date): self
    {
        $this->dateSent = $date;
        return $this;
    }

    public function isInbound(): bool
    {
        return self::DIRECTION_INBOUND === $this->direction;
    }

    public function isOutbound(): bool
    {
        return self::DIRECTION_OUTBOUND === $this->direction;
    }

    public function markSent(?string $externalId = null): self
    {
        $this->status = self::STATUS_SENT;
        $this->dateSent = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        if (null !== $externalId) {
            $this->externalMessageId = $externalId;
        }
        return $this;
    }

    public function markFailed(string $error): self
    {
        $this->status = self::STATUS_FAILED;
        $this->errorMessage = $error;
        return $this;
    }

    public static function loadMetadata(\Doctrine\ORM\Mapping\ClassMetadata $metadata): void
    {
        if (!class_exists(\Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder::class)) {
            return;
        }
        $builder = new \Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder($metadata);
        $builder->setTable('omnichannel_message_log')
            ->setCustomRepositoryClass(MessageLogRepository::class)
            ->addIndex(['conversation_id'], 'omni_msg_conversation')
            ->addIndex(['lead_id'], 'omni_msg_lead')
            ->addIndex(['channel', 'direction'], 'omni_msg_channel_dir')
            ->addIndex(['external_message_id'], 'omni_msg_external');
        $builder->addId();
        $builder->addNullableField('conversationId', \Doctrine\DBAL\Types\Types::INTEGER, 'conversation_id');
        $builder->addNullableField('leadId', \Doctrine\DBAL\Types\Types::INTEGER, 'lead_id');
        $builder->addField('channel', \Doctrine\DBAL\Types\Types::STRING);
        $builder->addField('direction', \Doctrine\DBAL\Types\Types::STRING, ['length' => 16]);
        $builder->addField('status', \Doctrine\DBAL\Types\Types::STRING, ['length' => 32]);
        $builder->addField('body', \Doctrine\DBAL\Types\Types::TEXT);
        $builder->addNullableField('externalMessageId', \Doctrine\DBAL\Types\Types::STRING, 'external_message_id');
        $builder->addNullableField('templateName', \Doctrine\DBAL\Types\Types::STRING, 'template_name');
        $builder->addNamedField('usedTemplate', \Doctrine\DBAL\Types\Types::BOOLEAN, 'used_template');
        $builder->addNullableField('errorMessage', \Doctrine\DBAL\Types\Types::TEXT, 'error_message');
        $builder->addNullableField('media', \Doctrine\DBAL\Types\Types::JSON, 'media');
        $builder->addNullableField('rawPayload', \Doctrine\DBAL\Types\Types::JSON, 'raw_payload');
        $builder->addNullableField('dateAdded', \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, 'date_added');
        $builder->addNullableField('dateSent', \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, 'date_sent');
    }

}
