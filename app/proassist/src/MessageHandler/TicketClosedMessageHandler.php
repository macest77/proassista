<?php

namespace App\MessageHandler;

use App\Message\TicketClosedMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class TicketClosedMessageHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(TicketClosedMessage $message): void
    {
        // symulacja wysłania maila
        $this->logger->info('Sending email for closed ticket', [
            'ticketId'    => $message->ticketId,
            'ticketTitle' => $message->ticketTitle,
            'status'      => $message->status,
            'to'          => $message->assignedTechnicianEmail ?? 'no technician assigned',
        ]);

        // $this->mailer->send(...)
        $this->logger->info(sprintf(
            '[MAIL SIMULATION] Ticket #%d "%s" has been %s. Notification sent to: %s',
            $message->ticketId,
            $message->ticketTitle,
            $message->status,
            $message->assignedTechnicianEmail ?? 'no one',
        ));
    }
}
