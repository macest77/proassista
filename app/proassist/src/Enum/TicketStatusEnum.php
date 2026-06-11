<?php

namespace App\Enum;

enum TicketStatusEnum: string {
    case NEW        = 'new';
    case ASSIGNED   = 'assigned';
    case IN_PROGRESS = 'in_progress';
    case DONE       = 'done';
    case CANCELLED  = 'cancelled';
}
