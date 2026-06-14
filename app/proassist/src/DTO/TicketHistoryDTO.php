<?php

namespace App\DTO;

use App\Entity\Technician;
use App\Entity\Ticket;
use App\Enum\TicketStatusEnum;
use Symfony\Component\Validator\Constraints as Assert;

class TicketHistoryDTO
{
    #[Assert\NotNull(message: 'Ticket is required.')]
    public readonly Ticket $ticket;

    #[Assert\NotNull(message: 'Old status is required.')]
    public readonly TicketStatusEnum $oldStatus;

    #[Assert\NotNull(message: 'New status is required.')]
    public readonly TicketStatusEnum $newStatus;

    #[Assert\NotNull(message: 'Technician is required.')]
    public readonly Technician $changedBy;
    public readonly \DateTimeImmutable $changedAt;

    public function __construct(
        Ticket $ticket,
        Technician $changedBy,
        TicketStatusEnum $oldStatus,
        TicketStatusEnum $newStatus
    ) {
        $this->ticket = $ticket;
        $this->changedBy = $changedBy;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->changedAt = (new \DateTimeImmutable());
    }

    public static function fromArray(array $data): self
    {
        $dto = self::from($data);
        $dto->changedAt = (new \DateTimeImmutable());

        return $dto;
    }

    private static function from(array $data): self
    {
        return new self(
            ticket:               array_key_exists('title', $data)               ? $data['ticket']               : null,
            oldStatus:            array_key_exists('oldStatus', $data)           ? $data['oldStatus']            : null,
            newStatus:            array_key_exists('newStatus', $data)           ? $data['newStatus']            : null,
            changedBy:            array_key_exists('changedBy', $data)           ? $data['changedBy']            : null,
        );
    }
}
