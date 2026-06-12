<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Enum\TicketOrderField;
use App\Enum\TicketPriorityEnum;
use App\Enum\TicketStatusEnum;
use App\Repository\TicketRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class TicketCollectionProvider implements ProviderInterface
{
    private const ALLOWED_ORDER_FIELDS = ['createdAt', 'updatedAt', 'priority', 'status', 'title'];
    private const DEFAULT_PAGE     = 1;
    private const DEFAULT_LIMIT     = 10;
    private const MAX_LIMIT        = 100;

    public function __construct(
        private readonly TicketRepository $ticketRepository,
        private readonly RequestStack     $requestStack,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $query   = $request?->query->all() ?? [];

        $filters  = $this->resolveFilters($query);
        $orderBy  = $this->resolveOrderBy($query);
        $page     = $this->resolveInt($query['page']  ?? null, self::DEFAULT_PAGE, 1);
        $limit    = $this->resolveInt($query['limit'] ?? null, self::DEFAULT_LIMIT, 1, self::MAX_LIMIT);
        $offset   = ($page - 1) * $limit;

        return $this->ticketRepository->findByFilters(
            filters:  $filters,
            orderBy:  $orderBy,
            limit:    $limit,
            offset:   $offset,
        );
    }

    private function resolveFilters(array $query): array
    {
        $filters = [];

        if (!empty($query['status'])) {
            try {
                $filters['status'] = TicketStatusEnum::from(strtolower($query['status']));
            } catch (\ValueError) {
                // invalid value — ignore filter, don't crash
            }
        }

        if (!empty($query['priority'])) {
            try {
                $filters['priority'] = TicketPriorityEnum::from(strtolower($query['priority']));
            } catch (\ValueError) {
                // invalid value — ignore filter, don't crash
            }
        }

        if (!empty($query['serialNumber'])) {
            $filters['serialNumber'] = $query['serialNumber'];
        }

        return $filters;
    }

    private function resolveOrderBy(array $query): array
    {
        $orderBy = ['createdAt' => 'DESC']; // default

        if (empty($query['order']) || !is_array($query['order'])) {
            return $orderBy;
        }

        $resolved = [];
        foreach ($query['order'] as $field => $direction) {
            if (!TicketOrderField::tryFrom($field)) {
                continue;
            }

            $dir = strtoupper($direction);
            if (!in_array($dir, ['ASC', 'DESC'], true)) {
                continue;
            }

            $resolved[$field] = $dir;
        }

        return $resolved ?: $orderBy;
    }

    private function resolveInt(mixed $value, int $default, int $min, int $max = PHP_INT_MAX): int
    {
        if ($value === null || !is_numeric($value)) {
            return $default;
        }

        return max($min, min($max, (int) $value));
    }
}
