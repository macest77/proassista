<?php

namespace App\Message;

final class TicketClosedMessage
{
    public function __construct(
        public readonly int    $ticketId,
        public readonly string $ticketTitle,
        public readonly string $status,
        public readonly ?string $assignedTechnicianEmail = null,
    ) {}
}
