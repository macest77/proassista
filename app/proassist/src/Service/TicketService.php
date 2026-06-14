<?php

namespace App\Service;

use App\DTO\CreateTicketDTO;
use App\DTO\TicketHistoryDTO;
use App\DTO\UpdateTicketDTO;
use App\Entity\Device;
use App\Entity\Technician;
use App\Entity\Ticket;
use App\Entity\TicketHistory;
use App\Enum\TicketPriorityEnum;
use App\Enum\TicketStatusEnum;
use App\Message\TicketClosedMessage;
use App\Repository\DeviceRepository;
use App\Repository\TechnicianRepository;
use App\Repository\TicketHistoryRepository;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class TicketService
{
    private $repository;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TechnicianRepository   $technicianRepository,
        private readonly DeviceRepository       $deviceRepository,
        private readonly Security               $security,
        private readonly TicketHistoryService   $ticketHistoryService,
        private readonly MessageBusInterface    $bus,
    ) {
        $this->repository = $this->em->getRepository(Ticket::class);
    }

    public function getTicket(int $ticket): ?Ticket
    {
        return $this->repository->find($ticket);
    }

    public function getAllTickets(): array
    {
        return $this->repository->findAll();
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

    public function updateTicket(Ticket $ticket, UpdateTicketDTO $dto): Ticket
    {
        $oldStatus = $ticket->getStatus();
        if ($dto->title !== null) {
            $ticket->setTitle($dto->title);
        }

        if ($dto->description !== null) {
            $ticket->setDescription($dto->description);
        }

        if ($dto->priority !== null) {
            $ticket->setPriority($dto->priority);
        }

        if ($dto->assignedTechnicianId !== null) {
            if (!$this->security->isGranted('ROLE_ADMIN')) {
                throw new AccessDeniedException('Only admin can assign a technician.');
            }

            $technician = $this->technicianRepository->find($dto->assignedTechnicianId);
            if (!$technician) {
                throw new \InvalidArgumentException("Technician #{$dto->assignedTechnicianId} not found.");
            }

            if (!$technician->isActive()) {
                throw new \InvalidArgumentException("Technician #{$dto->assignedTechnicianId} is not active.");
            }
            $ticket->setAssignedTechnician($technician);
            if ($dto->status === null) {
                $newStatus = TicketStatusEnum::ASSIGNED;
                $ticket->setStatus($newStatus);
                $historyDto = TicketHistoryDTO::fromArray([
                    'ticket' => $ticket,
                    'oldStatus' => $oldStatus,
                    'newStatus' => $newStatus,
                    'changedBy' => $this->security->getUser()
                ]);
                $this->ticketHistoryService->addHistory($historyDto);
            }
        }

        if ($dto->deviceId !== null) {
            $device = $this->deviceRepository->find($dto->deviceId);
            if (!$device) {
                throw new \InvalidArgumentException("Device #{$dto->deviceId} not found.");
            }
            $ticket->setDevice($device);
        }

        if ($dto->status !== null) {
            if (!$this->statusFlow($dto->status, $oldStatus)) {
                throw new \InvalidArgumentException("Invalid status change.");
            }
            $ticket->setStatus($dto->status);

            if (in_array($dto->status, [TicketStatusEnum::DONE, TicketStatusEnum::CANCELLED], true)) {
                $ticket->setClosedAt(new \DateTimeImmutable());
            } else {
                $ticket->setClosedAt(null);
            }
            $historyDto = TicketHistoryDTO::fromArray([
                'ticket' => $ticket,
                'oldStatus' => $oldStatus,
                'newStatus' => $dto->status,
                'changedBy' => $this->security->getUser()
            ]);
            $this->ticketHistoryService->addHistory($historyDto);

            // message if ticket is closed
            if (in_array($dto->status, [TicketStatusEnum::DONE, TicketStatusEnum::CANCELLED], true)) {
                $this->bus->dispatch(new TicketClosedMessage(
                    ticketId:               $ticket->getId(),
                    ticketTitle:            $ticket->getTitle(),
                    status:                 $dto->status->value,
                    assignedTechnicianEmail: $ticket->getAssignedTechnician()?->getEmail(),
                ));
            }
        }

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

    private function statusFlow(TicketStatusEnum $newStatus, TicketStatusEnum $oldStatus): bool
    {
        if ($newStatus !== TicketStatusEnum::CANCELLED) {
            switch ($oldStatus) {
                case TicketStatusEnum::NEW :
                    $check = TicketStatusEnum::ASSIGNED;
                    break;
                case TicketStatusEnum::ASSIGNED :
                    $check = TicketStatusEnum::IN_PROGRESS;
                    break;
                case TicketStatusEnum::IN_PROGRESS :
                    $check = TicketStatusEnum::DONE;
                    break;
                default:
                    $check = TicketStatusEnum::CANCELLED;
            }

            return $check === $newStatus;
        }

        return true;
    }

}
