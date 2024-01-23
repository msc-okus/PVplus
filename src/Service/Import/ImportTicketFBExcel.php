<?php

namespace App\Service\Import;

use App\Entity\Anlage;
use App\Entity\Ticket;
use App\Entity\TicketDate;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Repository\StatusRepository;
use App\Repository\TicketRepository;
use App\Service\FunctionsService;
use App\Service\MessageService;
use App\Service\PdoService;
use App\Service\WeatherFunctionsService;
use App\Service\WeatherServiceNew;
use Doctrine\ORM\EntityManagerInterface;

class ImportTicketFBExcel
{
    public function __construct(
        private readonly PdoService              $pdoService,
        private readonly AnlagenRepository       $anlagenRepository,
        private readonly WeatherServiceNew       $weather,
        private readonly WeatherFunctionsService $weatherFunctions,
        private readonly AnlagenRepository       $AnlRepo,
        private readonly EntityManagerInterface  $em,
        private readonly MessageService          $mailservice,
        private readonly FunctionsService        $functions,
        private readonly StatusRepository        $statusRepo,
        private readonly TicketRepository        $ticketRepo)
    {
    }


    use G4NTrait;

    public function import(Anlage $anlage, $fileStream, string $separator = ';', string $dateFormat = "d.m.y H:i"): string
    {
        $output = "";
        if ($fileStream) { // && $file->getMimeType() === 'text/plain'
            for ($n = 1; $n <= 0; $n++) {
                fgetcsv($fileStream, null, $separator);
            }
            $headline = fgetcsv($fileStream, null, $separator);
            $keyStamp       = array_search('Datetime', $headline);
            $keyHourStamp   = array_search('Hourly timestamp', $headline);
            $keyExcludeHour = array_search('Exclude hour', $headline);
            $keyReplaceHour = array_search('Replace hour', $headline);
            $keyExclude     = array_search('Exclude', $headline);
            $keyReplace     = array_search('Replace', $headline);
            $keyEGrid       = array_search('E_Grid', $headline);


            $oldStamp = null;

            $ticketArray = [];
            $ticketNo = 0;
            $ticketCategory = null;
            $oldTicketCartegory = 0;
            $oldTicketStartDate = $oldTicketStartDateHour = null;

            while ($row = fgetcsv($fileStream, null, $separator)){
                $counter++; // für debug
                $timeZone = null; //new \DateTimeZone('UTC');
                $stamp          = date_create_from_format($dateFormat, $row[$keyStamp], $timeZone);
                $stampEnd       = date_create(date('Y-m-d H:i', $stamp->getTimestamp()+900));
                $stampHelper    = $stamp->getTimestamp()-900;
                $hourStampStart = date_create(date('Y-m-d H:15', $stampHelper));
                $hourStampEnd   = date_create(date('Y-m-d H:15', $stampHelper+3600));

                if ($row[$keyExcludeHour] === 'WAHR' && $row[$keyReplaceHour] === 'FALSCH'){
                    $ticketCategory = 72;
                } elseif ($row[$keyExcludeHour] === 'FALSCH' && $row[$keyReplaceHour] === 'WAHR') {
                    $ticketCategory = 73;
                } else {
                    $ticketCategory = 0;
                }

                if ($ticketCategory !== $oldTicketCartegory && $ticketCategory > 0) {
                    $ticketNo++;
                }

                if ($ticketCategory > 0) {
                    if ($ticketCategory !== $oldTicketCartegory) {
                        $oldTicketStartDateHour = $hourStampStart;
                        $oldTicketStartDate     = $stamp;
                    }
                    $ticketArray[$ticketNo]['category'] = $ticketCategory;
                    $ticketArray[$ticketNo]['useHour'] = true;
                    $ticketArray[$ticketNo]['startDateHour'] = $oldTicketStartDateHour;
                    $ticketArray[$ticketNo]['endDateHour'] = $hourStampEnd;

                    $ticketArray[$ticketNo]['startDate'] = $oldTicketStartDate;
                    $ticketArray[$ticketNo]['endDate'] = $stampEnd;
                }

                $oldTicketCartegory = $ticketCategory;
            }
            fclose($fileStream);

            $this->generateTickets($anlage, $ticketArray);
        }

        return $output;
    }

    private function generateTickets(Anlage $anlage, array $ticketArray): void
    {
        foreach ($ticketArray as $ticketRow) {
            $ticket = new Ticket();
            $ticketDate = new TicketDate();
            $ticketDate->setAnlage($anlage);
            $ticketDate->setStatus('10');
            $ticketDate->setSystemStatus(10);
            $ticketDate->setPriority(10);
            $ticketDate->setCreatedBy("ImportSystem");
            $ticketDate->setUpdatedBy("ImportSystem");
            $ticket->setAnlage($anlage);
            $ticket->setStatus('10'); // Status 10 = open
            $ticket->setEditor('ImportSystem');
            $ticket->setSystemStatus(10);
            $ticket->setPriority(10);
            $ticket->setOpenTicket(false);
            $ticket->setCreatedBy("ImportSystem");
            $ticket->setUpdatedBy("ImportSystem");
            $ticket->setProofAM(false);
            $ticket->setCreationLog("via Import from FB Excel List");
            $ticket->setAlertType($ticketRow['category']); //  category = alertType (bsp: datagap, inverter power, etc.)
            $ticketDate->setAlertType($ticketRow['category']);
            $ticket->setErrorType(null); // type = errorType (Bsp:  SOR, EFOR, OMC)
            $ticketDate->setErrorType(null);
            $ticket->setInverter('*');
            $ticketDate->setInverter('*');
            $ticket->setDescription('');
            $ticketDate->setDescription('');

            $ticketDate->setUseHour($ticketRow['useHour']);

            if ($ticketRow['useHour']) {
                $ticket->setBegin($ticketRow['startDateHour']);
                $ticketDate->setBegin($ticketRow['startDateHour']);
                $ticketDate->setBeginHidden($ticketRow['startDate']);

                $ticket->setEnd($ticketRow['endDateHour']);
                $ticketDate->setEnd($ticketRow['endDateHour']);
                $ticketDate->setEndHidden($ticketRow['endDate']);
            } else {
                $ticket->setBegin($ticketRow['startDate']);
                $ticketDate->setBegin($ticketRow['startDate']);
                $ticketDate->setBeginHidden($ticketRow['startDateHour']);

                $ticket->setEnd($ticketRow['endDate']);
                $ticketDate->setEnd($ticketRow['endDate']);
                $ticketDate->setEndHidden($ticketRow['endDateHour']);
            }

            $ticket->setScope([20, 30]);
            $ticket->setKpiStatus(10);

            $ticket->addDate($ticketDate);

            $this->em->persist($ticket);
            $this->em->persist($ticketDate);
            $this->em->flush();
        }
    }
}