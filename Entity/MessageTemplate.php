<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Entity;

/**
 * Message template entity (WhatsApp / WeChat approved templates).
 *
 * Designed to map to table: omnichannel_message_templates
 */
class MessageTemplate
{
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_PENDING   = 'pending';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_REJECTED  = 'rejected';
    public const STATUS_DISABLED  = 'disabled';

    private ?int $id = null;
    private string $name = '';
    private string $channel = 'whatsapp';
    private string $language = 'en_US';
    private string $category = 'MARKETING';
    private string $status = self::STATUS_DRAFT;
    private string $body = '';
    /** @var array<int, string> */
    private array $parameters = [];
    private ?string $externalId = null;
    private ?string $rejectionReason = null;
    private ?\DateTimeInterface $dateAdded = null;
    private ?\DateTimeInterface $dateModified = null;
    private ?\DateTimeInterface $dateApproved = null;
    private bool $isPublished = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
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

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $language;
        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;
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

    /** @return array<int, string> */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /** @param array<int, string> $parameters */
    public function setParameters(array $parameters): self
    {
        $this->parameters = array_values($parameters);
        return $this;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): self
    {
        $this->externalId = $externalId;
        return $this;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?string $reason): self
    {
        $this->rejectionReason = $reason;
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

    public function getDateApproved(): ?\DateTimeInterface
    {
        return $this->dateApproved;
    }

    public function setDateApproved(?\DateTimeInterface $date): self
    {
        $this->dateApproved = $date;
        return $this;
    }

    public function getIsPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $published): self
    {
        $this->isPublished = $published;
        return $this;
    }

    public function isApproved(): bool
    {
        return self::STATUS_APPROVED === $this->status;
    }

    public function canBeSent(): bool
    {
        return $this->isPublished && $this->isApproved();
    }

    /** @return list<string> */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_DISABLED,
        ];
    }

    /**
     * Doctrine metadata (called by Mautic when the entity is registered).
     * Uses ClassMetadataBuilder when Mautic core is available.
     */
    public static function loadMetadata(\Doctrine\ORM\Mapping\ClassMetadata $metadata): void
    {
        if (!class_exists(\Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder::class)) {
            return;
        }
        $builder = new \Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder($metadata);
        $builder->setTable('omnichannel_message_templates')
            ->setCustomRepositoryClass(MessageTemplateRepository::class)
            ->addIndex(['channel', 'status'], 'omni_tpl_channel_status')
            ->addIndex(['name', 'channel'], 'omni_tpl_name_channel');
        $builder->addIdColumns();
        $builder->addNameField();
        $builder->addNullableField('channel', \Doctrine\DBAL\Types\Types::STRING, 'channel');
        $builder->addNullableField('language', \Doctrine\DBAL\Types\Types::STRING, 'language');
        $builder->addNullableField('category', \Doctrine\DBAL\Types\Types::STRING, 'category');
        $builder->addNullableField('status', \Doctrine\DBAL\Types\Types::STRING, 'status');
        $builder->addField('body', \Doctrine\DBAL\Types\Types::TEXT);
        $builder->addNullableField('parameters', \Doctrine\DBAL\Types\Types::JSON, 'parameters');
        $builder->addNullableField('externalId', \Doctrine\DBAL\Types\Types::STRING, 'external_id');
        $builder->addNullableField('rejectionReason', \Doctrine\DBAL\Types\Types::TEXT, 'rejection_reason');
        $builder->addNullableField('dateApproved', \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, 'date_approved');
    }

}
