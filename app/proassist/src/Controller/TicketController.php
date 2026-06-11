<?php

namespace App\Controller;

use App\DTO\CreateTicketDTO;
use App\Entity\Ticket;
use App\Enum\TicketPrioriyuEnum;
use App\Enum\TicketStatusEnum;
use App\Service\TicketService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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
        return $this->json([
            'message    ' => 'Welcome to your new controller!',
            'path' => 'src/Controller/TicketController.php',
        ]);
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
                    'priority' => array_column(TicketPrioriyuEnum::cases(), 'value'),
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

        return $this->json($this->serialize($ticket), Response::HTTP_CREATED);
    }

    private function serialize(Ticket $ticket): array
    {
        return [
            'id'                  => (string) $ticket->getId(),
            'title'               => $ticket->getTitle(),
            'description'         => $ticket->getDescription(),
            'priority'            => $ticket->getPriority()->value,
            'status'              => $ticket->getStatus()->value,
            'assignedTechnician'  => $ticket->getAssignedTechnician()?->getId(),
            'device'              => $ticket->getDevice()?->getId(),
            'createdAt'           => $ticket->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt'           => $ticket->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
            'closedAt'            => $ticket->getClosedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
