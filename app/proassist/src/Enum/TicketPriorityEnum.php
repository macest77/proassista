<?php

namespace App\Enum;

enum TicketPriorityEnum: string {
    case LOW        = 'low';
    case MEDIUM     = 'medium';
    case HIGH       = 'high';
    case CRITICAL   = 'critical';
}
