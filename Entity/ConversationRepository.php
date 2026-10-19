<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Conversation>
 */
class ConversationRepository extends CommonRepository
{
    public function getTableAlias(): string
    {
        return 'oc';
    }

    public function findOneByChannelAndIdentifier(string $channel, string $contactIdentifier): ?Conversation
    {
        return $this->findOneBy([
            'channel'            => $channel,
            'contactIdentifier'  => $contactIdentifier,
        ]);
    }

    /**
     * @param array<string, mixed> $filters
     * @return list<Conversation>
     */
    public function getConversations(array $filters = [], int $limit = 50, int $start = 0): array
    {
        $qb = $this->createQueryBuilder('oc');

        if (!empty($filters['channel'])) {
            $qb->andWhere('oc.channel = :channel')->setParameter('channel', $filters['channel']);
        }
        if (!empty($filters['status'])) {
            $qb->andWhere('oc.status = :status')->setParameter('status', $filters['status']);
        }
        if (isset($filters['leadId'])) {
            $qb->andWhere('oc.leadId = :leadId')->setParameter('leadId', (int) $filters['leadId']);
        }

        return $qb->orderBy('oc.dateModified', 'DESC')
            ->setFirstResult($start)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
