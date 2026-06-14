<?php

namespace App\DTO;

class AssignTicketDTO
{
    public readonly ?int $assignedTechnicianId;

    public function __construct(
        ?int      $assignedTechnicianId = null,
    ) {
        $this->assignedTechnicianId = $assignedTechnicianId;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            assignedTechnicianId: array_key_exists('assignedTechnicianId', $data) ? (int) $data['assignedTechnicianId'] : null,
        );
    }
}
