<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<MessageLog>
 */
class MessageLogRepository extends CommonRepository
{
    public function getTableAlias(): string
    {
        return 'oml';
    }

    /**
     * @return list<MessageLog>
     */
    public function findByConversation(int $conversationId, int $limit = 200): array
    {
        return $this->createQueryBuilder('oml')
            ->where('oml.conversationId = :cid')
            ->setParameter('cid', $conversationId)
            ->orderBy('oml.dateAdded', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
