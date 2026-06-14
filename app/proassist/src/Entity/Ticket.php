<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response as OpenApiResponse;
use App\Controller\TicketController;
use App\Enum\TicketPriorityEnum;
use App\Enum\TicketStatusEnum;
use App\Repository\TicketRepository;
use App\State\AssignTicketProcessor;
use App\State\TicketCollectionProvider;
use ArrayObject;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/tickets',
            controller: TicketController::class . '::createTicket',
        ),
        new GetCollection(
            uriTemplate: '/tickets',
            provider: TicketCollectionProvider::class,
        ),
        new Get(
            uriTemplate: '/tickets/{id}',
            controller: TicketController::class . '::showTicket',
        ),
        new Patch(
            uriTemplate: '/tickets/{id}',
            controller: TicketController::class . '::editTicket',
        ),
        new Post(
            uriTemplate: '/tickets/{id}/assign',
            openapi: new OpenApiOperation(
                tags: ['Tickets'],
                responses: [
                    '200' => new OpenApiResponse(description: 'Technician assigned successfully'),
                    '403' => new OpenApiResponse(description: 'Access denied — requires ROLE_ADMIN'),
                    '422' => new OpenApiResponse(description: 'Technician not found or not active'),
                ],
                summary: 'Assign a technician to a ticket',
                description: 'Assigns an active technician to the ticket, sets status to ASSIGNED and saves history. Requires ROLE_ADMIN.',
                requestBody: new RequestBody(
                    description: 'Technician to assign',
                    content: new ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type'       => 'object',
                                'required'   => ['technicianId'],
                                'properties' => [
                                    'technicianId' => [
                                        'type'        => 'integer',
                                        'example'     => 5,
                                        'description' => 'ID of the active technician to assign',
                                    ],
                                ],
                            ],
                        ],
                    ]),
                ),
            ),
            security: 'is_granted("ROLE_ADMIN")',
            securityMessage: 'Only admins can assign technicians.',
            deserialize: false,
            name: 'assign_technician',
            provider: ItemProvider::class,
            processor: AssignTicketProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['ticket:read']],
    denormalizationContext: ['groups' => ['ticket:write']]
)]
class Ticket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ticket:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['ticket:read', 'ticket:write'])]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['ticket:read', 'ticket:write'])]
    private ?string $description = null;

    #[ORM\Column(enumType: TicketPriorityEnum::class)]
    #[Groups(['ticket:read', 'ticket:write'])]
    private ?TicketPriorityEnum $priority = null;

    #[ORM\Column(enumType: TicketStatusEnum::class)]
    #[Groups(['ticket:read', 'ticket:write'])]
    private ?TicketStatusEnum $status = null;

    #[ORM\Column]
    #[Groups(['ticket:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ticket:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ticket:read'])]
    private ?\DateTimeImmutable $closedAt = null;

    #[ORM\ManyToOne(inversedBy: 'tickets')]
    #[Groups(['ticket:read', 'ticket:write'])]
    private ?Technician $assignedTechnician = null;

    #[ORM\ManyToOne(inversedBy: 'tickets')]
    #[Groups(['ticket:read', 'ticket:write'])]
    private ?Device $device = null;

    /**
     * @var Collection<int, TicketHistory>
     */
    #[ORM\OneToMany(targetEntity: TicketHistory::class, mappedBy: 'ticket')]
    private Collection $ticketHistories;

    public function __construct()
    {
        $this->createdAt        = new \DateTimeImmutable();
        $this->status           = TicketStatusEnum::NEW;
        $this->ticketHistories  = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPriority(): ?TicketPriorityEnum
    {
        return $this->priority;
    }

    public function setPriority(TicketPriorityEnum $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getStatus(): ?TicketStatusEnum
    {
        return $this->status;
    }

    public function setStatus(TicketStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function setClosedAt(?\DateTimeImmutable $closedAt): static
    {
        $this->closedAt = $closedAt;

        return $this;
    }

    public function getAssignedTechnician(): ?Technician
    {
        return $this->assignedTechnician;
    }

    public function setAssignedTechnician(?Technician $assignedTechnician): static
    {
        $this->assignedTechnician = $assignedTechnician;

        return $this;
    }

    public function getDevice(): ?Device
    {
        return $this->device;
    }

    public function setDevice(?Device $device): static
    {
        $this->device = $device;

        return $this;
    }

    /**
     * @return Collection<int, TicketHistory>
     */
    public function getTicketHistories(): Collection
    {
        return $this->ticketHistories;
    }

    public function addTicketHistory(TicketHistory $ticketHistory): static
    {
        if (!$this->ticketHistories->contains($ticketHistory)) {
            $this->ticketHistories->add($ticketHistory);
            $ticketHistory->setTicket($this);
        }

        return $this;
    }

    public function removeTicketHistory(TicketHistory $ticketHistory): static
    {
        if ($this->ticketHistories->removeElement($ticketHistory)) {
            // set the owning side to null (unless already changed)
            if ($ticketHistory->getTicket() === $this) {
                $ticketHistory->setTicket(null);
            }
        }

        return $this;
    }
}
