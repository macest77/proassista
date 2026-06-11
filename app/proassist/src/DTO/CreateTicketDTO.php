<?php

namespace App\DTO;

use App\Enum\TicketPrioriyuEnum;
use App\Enum\TicketStatusEnum;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateTicketDTO
{
    #[Assert\NotBlank(message: 'Title is required.')]
    #[Assert\Length(min: 3, max: 255, minMessage: 'Title must be at least 3 characters.')]
    public readonly string $title;

    #[Assert\Length(max: 5000)]
    public readonly ?string $description;

    #[Assert\NotNull(message: 'Priority is required.')]
    public readonly TicketPrioriyuEnum $priority;

    #[Assert\NotNull(message: 'Status is required.')]
    public readonly TicketStatusEnum $status;

    #[Assert\Length(max: 255)]
    public readonly ?string $assignedTechnician;

    #[Assert\Length(max: 255)]
    public readonly ?string $device;

    public function __construct(
        string  $title,
        TicketPrioriyuEnum $priority,
        TicketStatusEnum   $status,
        ?string $description = null,
        ?int    $assignedTechnician = null,
        ?int    $device = null,
    ) {
        $this->title               = $title;
        $this->priority            = $priority;
        $this->status              = $status;
        $this->description         = $description;
        $this->assignedTechnician  = $assignedTechnician;
        $this->device              = $device;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            title:              $data['title'] ?? '',
            priority:           isset($data['priority']) ? TicketPrioriyuEnum::from($data['priority']) : TicketPrioriyuEnum::LOW,
            status:             isset($data['status'])   ? TicketStatusEnum::from($data['status'])     : TicketStatusEnum::NEW,
            description:        $data['description']        ?? null,
            assignedTechnician: $data['assignedTechnician'] ?? null,
            device:             $data['device']             ?? null,
        );
    }
}
