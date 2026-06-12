<?php

namespace App\Enum;

enum TicketOrderField: string
{
    case CREATED_AT = 'createdAt';
    case UPDATED_AT = 'updatedAt';
    case PRIORITY   = 'priority';
    case STATUS     = 'status';
    case TITLE      = 'title';
}
