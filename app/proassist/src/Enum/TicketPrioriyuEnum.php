<?php

namespace App\Enum;

enum TicketPrioriyuEnum: string {
    case LOW        = 'low';
    case MEDIUM     = 'medium';
    case HIGH       = 'high';
    case CRITICAL   = 'critical';
}
