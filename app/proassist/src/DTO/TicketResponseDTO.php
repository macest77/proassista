<?php

namespace App\DTO;

use App\Entity\Ticket;

final class TicketResponseDTO
{
    public string $id;
    public string $title;
    public ?string $description;
    public string $priority;
    public string $status;
    public ?int $assignedTechnicianId;
    public ?string $assignedTechnicianName;
    public ?int $deviceId;
    public ?string $deviceSerialNumber;
    public ?string $deviceModel;
    public string $createdAt;
    public ?string $updatedAt;
    public ?string $closedAt;

    public static function fromEntity(Ticket $ticket): self
    {
        $dto = new self();
        $dto->id          = (string) $ticket->getId();
        $dto->title       = $ticket->getTitle();
        $dto->description = $ticket->getDescription();
        $dto->priority    = $ticket->getPriority()->value;
        $dto->status      = $ticket->getStatus()->value;
        $dto->createdAt   = $ticket->getCreatedAt()->format(\DateTimeInterface::ATOM);
        $dto->updatedAt   = $ticket->getUpdatedAt()?->format(\DateTimeInterface::ATOM);
        $dto->closedAt    = $ticket->getClosedAt()?->format(\DateTimeInterface::ATOM);

        $technician = $ticket->getAssignedTechnician();
        $dto->assignedTechnicianId   = $technician?->getId();
        $dto->assignedTechnicianName = $technician
            ? $technician->getFirstName() . ' ' . $technician->getLastName()
            : null;

        $device = $ticket->getDevice();
        $dto->deviceId           = $device?->getId();
        $dto->deviceSerialNumber = $device?->getSerialNumber();
        $dto->deviceModel        = $device?->getModel();

        return $dto;
    }
}
