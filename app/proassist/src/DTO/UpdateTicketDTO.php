<?php

namespace App\DTO;

use App\Enum\TicketPriorityEnum;
use App\Enum\TicketStatusEnum;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateTicketDTO
{
    #[Assert\Length(min: 3, max: 255)]
    public readonly ?string $title;

    #[Assert\Length(max: 5000)]
    public readonly ?string $description;

    public readonly ?TicketPriorityEnum $priority;

    public readonly ?TicketStatusEnum $status;

    public readonly ?int $assignedTechnicianId;

    public readonly ?int $deviceId;

    public function __construct(
        ?string   $title = null,
        ?string   $description = null,
        ?TicketPriorityEnum $priority = null,
        ?TicketStatusEnum   $status = null,
        ?int      $assignedTechnicianId = null,
        ?int      $deviceId = null,
    ) {
        $this->title                = $title;
        $this->description          = $description;
        $this->priority             = $priority;
        $this->status               = $status;
        $this->assignedTechnicianId = $assignedTechnicianId;
        $this->deviceId             = $deviceId;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            title:                array_key_exists('title', $data)               ? $data['title']               : null,
            description:          array_key_exists('description', $data)         ? $data['description']         : null,
            priority:             array_key_exists('priority', $data)            ? TicketPriorityEnum::from($data['priority']) : null,
            status:               array_key_exists('status', $data)              ? TicketStatusEnum::from($data['status']) : null,
            assignedTechnicianId: array_key_exists('assignedTechnicianId', $data) ? (int) $data['assignedTechnicianId'] : null,
            deviceId:             array_key_exists('deviceId', $data)            ? (int) $data['deviceId']      : null,
        );
    }
}
