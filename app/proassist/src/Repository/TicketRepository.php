<?php

namespace App\Repository;

use App\Entity\Ticket;
use App\Enum\TicketOrderField;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ticket>
 */
class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    public function findByFilters(
        array $filters = [],
        array $orderBy = ['createdAt' => 'DESC'],
        int   $limit = 10,
        int   $offset = 0,
    ): array {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.assignedTechnician', 'tech')
            ->addSelect('tech')
            ->leftJoin('t.device', 'd')
            ->addSelect('d');

        // --- filters ---

        if (isset($filters['status'])) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $qb->andWhere('t.priority = :priority')
                ->setParameter('priority', $filters['priority']);
        }

        if (isset($filters['serialNumber'])) {
            $qb->andWhere('d.serialNumber = :serialNumber')
                ->setParameter('serialNumber', $filters['serialNumber']);
        }

        // --- sorting ---

        foreach ($orderBy as $field => $direction) {
            if (!TicketOrderField::tryFrom($field)) {
                continue;
            }
            $qb->addOrderBy('t.' . $field, $direction);
        }

        // --- pagination ---

        $qb->setMaxResults($limit)
            ->setFirstResult($offset);

        return $qb->getQuery()->getResult();
    }
}
