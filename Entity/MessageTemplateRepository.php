<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<MessageTemplate>
 */
class MessageTemplateRepository extends CommonRepository
{
    public function getTableAlias(): string
    {
        return 'omt';
    }

    /**
     * @return list<MessageTemplate>
     */
    public function findApprovedForChannel(string $channel): array
    {
        return $this->createQueryBuilder('omt')
            ->where('omt.channel = :channel')
            ->andWhere('omt.status = :status')
            ->andWhere('omt.isPublished = true')
            ->setParameter('channel', $channel)
            ->setParameter('status', MessageTemplate::STATUS_APPROVED)
            ->orderBy('omt.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByNameAndChannel(string $name, string $channel): ?MessageTemplate
    {
        return $this->findOneBy(['name' => $name, 'channel' => $channel]);
    }

    /**
     * @param array<string, mixed> $filters
     * @return list<MessageTemplate>
     */
    public function getEntitiesByFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('omt');

        if (!empty($filters['channel'])) {
            $qb->andWhere('omt.channel = :channel')->setParameter('channel', $filters['channel']);
        }
        if (!empty($filters['status'])) {
            $qb->andWhere('omt.status = :status')->setParameter('status', $filters['status']);
        }
        if (isset($filters['isPublished'])) {
            $qb->andWhere('omt.isPublished = :pub')->setParameter('pub', (bool) $filters['isPublished']);
        }

        return $qb->orderBy('omt.dateModified', 'DESC')->getQuery()->getResult();
    }
}
