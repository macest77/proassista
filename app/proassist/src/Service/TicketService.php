<?php

namespace App\Service;

use App\DTO\CreateTicketDTO;
use App\Entity\Device;
use App\Entity\Technician;
use App\Entity\Ticket;
use App\Repository\DeviceRepository;
use App\Repository\TechnicianRepository;
use Doctrine\ORM\EntityManagerInterface;

class TicketService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TechnicianRepository   $technicianRepository,
        private readonly DeviceRepository       $deviceRepository,
    ) {}

    public function getAllTickets(): array
    {
        return $this->em->getRepository(Ticket::class)->findAll();
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function create(CreateTicketDTO $dto): Ticket
    {
        $technician = $this->resolveTechnician($dto->assignedTechnician);
        $device     = $this->resolveDevice($dto->device);

        $ticket = new Ticket();
        $ticket->setTitle($dto->title);
        $ticket->setDescription($dto->description);
        $ticket->setPriority($dto->priority);
        $ticket->setStatus($dto->status);
        $ticket->setAssignedTechnician($technician);
        $ticket->setDevice($device);

        $this->em->persist($ticket);
        $this->em->flush();

        return $ticket;
    }

    private function resolveTechnician(?int $id): ?Technician
    {
        if ($id === null) {
            return null;
        }

        $technician = $this->technicianRepository->find($id);

        if (!$technician) {
            throw new \InvalidArgumentException("Technician #$id not found.");
        }

        return $technician;
    }

    private function resolveDevice(?int $id): ?Device
    {
        if ($id === null) {
            return null;
        }

        $device = $this->deviceRepository->find($id);

        if (!$device) {
            throw new \InvalidArgumentException("Device #$id not found.");
        }

        return $device;
    }

}
