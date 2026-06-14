<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\State\TechnicianPerformanceProvider;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/reports/technicians-performance',
            provider: TechnicianPerformanceProvider::class,
            security: 'is_granted("ROLE_ADMIN")',
            securityMessage: 'Only admins can view performance reports.',
        ),
    ],
    paginationEnabled: false,
)]
final class TechnicianPerformance
{
    public function __construct(
        public readonly int    $technicianId,
        public readonly string $name,
        public readonly int    $closedTickets,
        public readonly float  $averageClosingTimeHours,
    ) {}
}
