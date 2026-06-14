<?php

namespace App\Repository;

use App\Entity\Technician;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Technician>
 */
class TechnicianRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Technician::class);
    }

    public function findTechnicianPerformance(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
        SELECT
            t.id AS \"technicianId\",
            t.first_name AS \"firstName\",
            t.last_name AS \"lastName\",
            COUNT(tk.id) AS \"closedTickets\",
            COALESCE(
                AVG(
                    EXTRACT(EPOCH FROM (tk.closed_at - h.assigned_at)) / 3600
                ),
                0
            ) AS \"averageClosingTimeHours\"
        FROM technician t
        LEFT JOIN ticket tk
            ON tk.assigned_technician_id = t.id
            AND tk.status IN ('DONE', 'CANCELLED')
            AND tk.closed_at IS NOT NULL
        LEFT JOIN (
            SELECT
                ticket_id,
                MIN(changed_at) AS assigned_at
            FROM ticket_history
            WHERE new_status = 'ASSIGNED'
            GROUP BY ticket_id
        ) h ON h.ticket_id = tk.id
        WHERE t.active = true
        GROUP BY t.id, t.first_name, t.last_name
        ORDER BY \"closedTickets\" DESC
    ";

        return $conn->executeQuery($sql)->fetchAllAssociative();
    }
}
