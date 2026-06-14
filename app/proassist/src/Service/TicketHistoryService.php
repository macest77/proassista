<?php

namespace App\Service;

use App\DTO\TicketHistoryDTO;
use App\Entity\TicketHistory;
use Doctrine\ORM\EntityManagerInterface;

class TicketHistoryService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function addHistory(TicketHistoryDTO $dto): TicketHistory
    {
        $history = new TicketHistory();
        $history->setTicket($dto->ticket);
        $history->setOldStatus($dto->oldStatus);
        $history->setNewStatus($dto->newStatus);
        $history->setChangedBy($dto->changedBy);
        $history->setChangedAt($dto->changedAt);

        $this->em->persist($history);
        $this->em->flush();

        return $history;
    }
}
