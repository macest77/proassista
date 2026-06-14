<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\TechnicianPerformance;
use App\Repository\TechnicianRepository;

final class TechnicianPerformanceProvider implements ProviderInterface
{
    public function __construct(
        private readonly TechnicianRepository $technicianRepository,
    ) {}

    /**
     * @return TechnicianPerformance[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $rows = $this->technicianRepository->findTechnicianPerformance();

        return array_map(
            fn(array $row) => new TechnicianPerformance(
                technicianId:             (int)   $row['technicianId'],
                name:                     $row['firstName'] . ' ' . $row['lastName'],
                closedTickets:            (int)   $row['closedTickets'],
                averageClosingTimeHours:  (float) round((float) $row['averageClosingTimeHours'], 2),
            ),
            $rows,
        );
    }
}
