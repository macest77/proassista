<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\DTO\AssignTicketDTO;
use App\DTO\TicketResponseDTO;
use App\Entity\Ticket;
use App\Service\TicketService;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AssignTicketProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly TicketService      $ticketService,
        private readonly ValidatorInterface $validator,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): TicketResponseDTO
    {
        /** @var Ticket $ticket */
        $ticket = $context['previous_data'] ?? null;

        if (!$ticket instanceof Ticket) {
            throw new \RuntimeException('Ticket not found.');
        }

        $dto = new AssignTicketDTO((int) ($data['technicianId'] ?? 0));

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
            throw new UnprocessableEntityHttpException(json_encode($errors));
        }

        try {
            $ticket = $this->ticketService->updateTicket($ticket, $dto);
        } catch (AccessDeniedException $e) {
            throw new AccessDeniedHttpException($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }

        return TicketResponseDTO::fromEntity($ticket);
    }
}
