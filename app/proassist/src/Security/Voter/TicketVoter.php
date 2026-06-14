<?php

namespace App\Security\Voter;

use App\Entity\Technician;
use App\Entity\Ticket;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TicketVoter extends Voter
{
    public const EDIT   = 'TICKET_EDIT';
    public const VIEW   = 'TICKET_VIEW';
    public const ASSIGN = 'TICKET_ASSIGN';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW, self::ASSIGN], true)
            && $subject instanceof Ticket;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof Technician) {
            return false;
        }

        /** @var Ticket $ticket */
        $ticket = $subject;

        return match ($attribute) {
            self::VIEW   => true,
            self::ASSIGN => in_array('ROLE_ADMIN', $user->getRoles(), true),
            self::EDIT   => $this->canEdit($ticket, $user),
            default      => false,
        };
    }

    private function canEdit(Ticket $ticket, Technician $user): bool
    {
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        return $ticket->getAssignedTechnician()?->getId() === $user->getId();
    }
}
