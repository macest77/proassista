<?php

namespace App\DataFixtures;

use App\Entity\Device;
use App\Entity\Technician;
use App\Entity\Ticket;
use App\Enum\TicketPriorityEnum;
use App\Enum\TicketStatusEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Technicians
        $technicians = [];
        $techData = [
            ['Jan',     'Kowalski',  'jan.kowalski@example.com'],
            ['Anna',    'Nowak',     'anna.nowak@example.com'],
            ['Piotr',   'Wiśniewski','piotr.wisniewski@example.com'],
            ['Katarzyna','Wójcik',   'katarzyna.wojcik@example.com'],
        ];

        foreach ($techData as [$first, $last, $email]) {
            $tech = new Technician();
            $tech->setFirstName($first);
            $tech->setLastName($last);
            $tech->setEmail($email);
            $tech->setActive(true);
            $manager->persist($tech);
            $technicians[] = $tech;
        }

        // Devices
        $devices = [];
        $deviceData = [
            ['SN-001', 'Dell XPS 15',        'Firma ABC'],
            ['SN-002', 'HP EliteBook 840',   'Jan Nowak'],
            ['SN-003', 'Lenovo ThinkPad X1', 'Firma XYZ'],
            ['SN-004', 'MacBook Pro 14',      'Anna Kowalska'],
            ['SN-005', 'Dell Latitude 5520',  'Firma ABC'],
        ];

        foreach ($deviceData as [$serial, $model, $customer]) {
            $device = new Device();
            $device->setSerialNumber($serial);
            $device->setModel($model);
            $device->setCustomerName($customer);
            $device->setCreatedAt(new \DateTimeImmutable());
            $device->setUpdatedAt(new \DateTimeImmutable());
            $manager->persist($device);
            $devices[] = $device;
        }

        $manager->flush();

        // Tickets
        $ticketData = [
            ['Laptop nie odpowiada',          'Ekran zgasł po aktualizacji',           TicketPriorityEnum::HIGH,     TicketStatusEnum::NEW,         0, 0],
            ['Brak dostępu do sieci',         'VPN przestało działać po restarcie',    TicketPriorityEnum::CRITICAL, TicketStatusEnum::ASSIGNED,    1, 1],
            ['Drukarka nie drukuje',          'Błąd sterownika drukarki',              TicketPriorityEnum::MEDIUM,   TicketStatusEnum::IN_PROGRESS, 2, 2],
            ['Wolny komputer',                'System bardzo wolno się uruchamia',     TicketPriorityEnum::LOW,      TicketStatusEnum::NEW,         null, 3],
            ['Błąd aplikacji księgowej',      'Program crashuje przy zamknięciu',      TicketPriorityEnum::HIGH,     TicketStatusEnum::ASSIGNED,    3, 4],
            ['Reset hasła',                   null,                                    TicketPriorityEnum::LOW,      TicketStatusEnum::DONE,        0, null],
            ['Wymiana baterii',               'Bateria nie trzyma ładowania',          TicketPriorityEnum::MEDIUM,   TicketStatusEnum::IN_PROGRESS, 1, 1],
            ['Instalacja oprogramowania',     'Potrzebna instalacja Adobe CC',         TicketPriorityEnum::LOW,      TicketStatusEnum::NEW,         null, 2],
            ['Awaria dysku twardego',         'Dysk wydaje dziwne dźwięki',            TicketPriorityEnum::CRITICAL, TicketStatusEnum::ASSIGNED,    2, 3],
            ['Problem z monitorem',           'Monitor miga przy jasnych obrazach',    TicketPriorityEnum::MEDIUM,   TicketStatusEnum::CANCELLED,   3, 0],
        ];

        foreach ($ticketData as [$title, $desc, $priority, $status, $techIdx, $deviceIdx]) {
            $ticket = new Ticket();
            $ticket->setTitle($title);
            $ticket->setDescription($desc);
            $ticket->setPriority($priority);
            $ticket->setStatus($status);

            if ($techIdx !== null) {
                $ticket->setAssignedTechnician($technicians[$techIdx]);
            }
            if ($deviceIdx !== null) {
                $ticket->setDevice($devices[$deviceIdx]);
            }

            if (in_array($status, [TicketStatusEnum::DONE, TicketStatusEnum::CANCELLED])) {
                $ticket->setClosedAt(new \DateTimeImmutable());
            }

            $manager->persist($ticket);
        }

        $manager->flush();
    }
}
