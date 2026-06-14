<?php

namespace App\Controller;

use App\DTO\AssignTicketDTO;
use App\DTO\CreateTicketDTO;
use App\DTO\TicketResponseDTO;
use App\DTO\UpdateTicketDTO;
use App\Entity\Ticket;
use App\Enum\TicketPriorityEnum;
use App\Enum\TicketStatusEnum;
use App\Security\Voter\TicketVoter;
use App\Service\TicketService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/tickets', name: 'api_tickets_')]
#[OA\Tag(name: 'Tickets')]
final class TicketController extends AbstractController
{
    public function __construct(
        private readonly TicketService      $ticketService,
        private readonly ValidatorInterface $validator,
    ) { }

    #[Route('', name: 'app_ticket')]
    public function index(): JsonResponse
    {
        return $this->json(
            array_map(fn(Ticket $t) => TicketResponseDTO::fromEntity($t), $this->ticketService->getAllTickets()),
            Response::HTTP_OK);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/tickets',
        summary: 'Create a new ticket',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                required: ['title', 'priority', 'status'],
                properties: [
                    new OA\Property(property: 'title',                type: 'string'),
                    new OA\Property(property: 'priority',             type: 'string', enum: ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL']),
                    new OA\Property(property: 'status',               type: 'string', enum: ['NEW', 'ASSIGNED', 'IN_PROGRESS', 'DONE', 'CANCELLED']),
                    new OA\Property(property: 'description',          type: 'string', nullable: true),
                    new OA\Property(property: 'assignedTechnicianId', type: 'integer', nullable: true),
                    new OA\Property(property: 'deviceId',             type: 'integer', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Ticket created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function createTicket(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $dto = CreateTicketDTO::fromArray($data);
        } catch (\ValueError $e) {
            return $this->json([
                'error'   => 'Invalid enum value.',
                'details' => $e->getMessage(),
                'allowed' => [
                    'priority' => array_column(TicketPriorityEnum::cases(), 'value'),
                    'status'   => array_column(TicketStatusEnum::cases(),   'value'),
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Validate DTO
        $violations = $this->validator->validate($dto);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
            return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $ticket = $this->ticketService->create($dto);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json(TicketResponseDTO::fromEntity($ticket), Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[OA\Get(
        path: '/api/tickets/{id}',
        summary: 'Retrieves the ticket',
        responses: [
            new OA\Response(response: 200, description: 'Ticket found'),
            new OA\Response(response: 422, description: 'Invalid ticket'),
        ]
    )]
    public function showTicket(int $id): JsonResponse
    {
        try {
            $ticket = $this->ticketService->getTicket($id);
            if (!$ticket) {
                throw new NotFoundHttpException();
            }
        } catch (NotFoundHttpException) {
            return $this->json(['error' => 'Invalid ticket'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json(TicketResponseDTO::fromEntity($ticket), Response::HTTP_OK, [], ['groups' => ['ticket:read']]);
    }

    #[Route('/{id}', name: 'edit', methods: ['PATCH'])]
    #[OA\Get(
        path: '/api/tickets/{id}',
        summary: 'Updates the ticket',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                required: ['title', 'priority', 'status'],
                properties: [
                    new OA\Property(property: 'title',                type: 'string'),
                    new OA\Property(property: 'priority',             type: 'string', enum: ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL']),
                    new OA\Property(property: 'status',               type: 'string', enum: ['NEW', 'ASSIGNED', 'IN_PROGRESS', 'DONE', 'CANCELLED']),
                    new OA\Property(property: 'description',          type: 'string', nullable: true),
                    new OA\Property(property: 'assignedTechnicianId', type: 'integer', nullable: true),
                    new OA\Property(property: 'deviceId',             type: 'integer', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Ticket updated'),
            new OA\Response(response: 404, description: 'Ticket not found'),
            new OA\Response(response: 422, description: 'Invalid ticket'),
        ]
    )]
    public function editTicket(Ticket $ticket, Request $request): JsonResponse
    {
        if (!($this->isGranted(TicketVoter::EDIT, $ticket))) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $dto = UpdateTicketDTO::fromArray($data);
        } catch (\ValueError $e) {
            return $this->json([
                'error' => 'Invalid enum value.',
                'details' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($dto->assignedTechnicianId !== null
            && $dto->assignedTechnicianId !== $ticket->getAssignedTechnician()->getId()) {

            if (!($this->isGranted(TicketVoter::ASSIGN, $ticket))) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }
        }

        return $this->handleUpdate($ticket, $dto);
    }

    public function assignTicket(Ticket $ticket, Request $request): JsonResponse
    {
        if (!($this->isGranted(TicketVoter::ASSIGN, $ticket))) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $dto = AssignTicketDTO::fromArray($data);
        } catch (\ValueError $e) {
            return $this->json([
                'error' => 'Invalid value.',
                'details' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->handleUpdate($ticket, $dto);
    }

    private function handleUpdate(Ticket $ticket,  UpdateTicketDTO|AssignTicketDTO $dto)
    {
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
            return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $ticket = $this->ticketService->updateTicket($ticket, $dto);
        } catch (AccessDeniedException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json(TicketResponseDTO::fromEntity($ticket));
    }
}
