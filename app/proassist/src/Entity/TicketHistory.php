<?php

namespace App\Entity;

use App\Enum\TicketStatusEnum;
use App\Repository\TicketHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TicketHistoryRepository::class)]
class TicketHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'ticketHistories')]
    private ?Ticket $ticket = null;

    #[ORM\Column(enumType: TicketStatusEnum::class)]
    private ?TicketStatusEnum $oldStatus = null;

    #[ORM\Column(enumType: TicketStatusEnum::class)]
    private ?TicketStatusEnum $newStatus = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $changedAt = null;

    #[ORM\ManyToOne(inversedBy: 'ticketHistories')]
    private ?Technician $changedBy = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTicket(): ?Ticket
    {
        return $this->ticket;
    }

    public function setTicket(?Ticket $ticket): static
    {
        $this->ticket = $ticket;

        return $this;
    }

    public function getOldStatus(): ?TicketStatusEnum
    {
        return $this->oldStatus;
    }

    public function setOldStatus(TicketStatusEnum $oldStatus): static
    {
        $this->oldStatus = $oldStatus;

        return $this;
    }

    public function getNewStatus(): ?TicketStatusEnum
    {
        return $this->newStatus;
    }

    public function setNewStatus(TicketStatusEnum $newStatus): static
    {
        $this->newStatus = $newStatus;

        return $this;
    }

    public function getChangedAt(): ?\DateTimeImmutable
    {
        return $this->changedAt;
    }

    public function setChangedAt(\DateTimeImmutable $changedAt): static
    {
        $this->changedAt = $changedAt;

        return $this;
    }

    public function getChangedBy(): ?Technician
    {
        return $this->changedBy;
    }

    public function setChangedBy(?Technician $changedBy): static
    {
        $this->changedBy = $changedBy;

        return $this;
    }
}
