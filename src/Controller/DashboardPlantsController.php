<?php

namespace App\Controller;

use App\Entity\Anlage;
use App\Entity\AnlageCase5;
use App\Entity\User;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service\AvailabilityService;
use App\Service\ChartService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardPlantsController extends BaseController
{
    use G4NTrait;

    /**
     * @throws Exception
     */
    #[Route(path: '/dashboard/plants/{eignerId}/{anlageId}', name: 'app_dashboard_plant')]
    public function index($eignerId, $anlageId, Request $request, AnlagenRepository $anlagenRepository, ChartService $chartService, EntityManagerInterface $entityManager, AvailabilityService $availabilityService): Response
    {
        $hour = '';
        $form = [];
        /* @var Anlage|null $aktAnlage */
        if ($anlageId && $anlageId > 0) {
            $aktAnlage = $anlagenRepository->findOneBy(['anlId' => $anlageId]);
        } else {
            $aktAnlage = null;
        }
        /* @var Anlage $anlagen */
        if ($eignerId) {
            if ($this->isGranted('ROLE_G4N')) {
                $anlagen = $anlagenRepository->findByEignerActive($eignerId, $anlageId);
            } else {
                /* @var User $user */
                $user = $this->getUser();
                $granted = $user->getGrantedArray();
                $anlagen = $anlagenRepository->findGrantedActive($eignerId, $anlageId, $granted);
            }
        }
        if ($request->request->get('mysubmit') === null || $request->request->all() === null) {
            $form['selectedChart'] = 'ac_single';
            $form['selectedGroup'] = 1;
            $form['selectedInverter'] = 1;
            $form['selectedSet'] = 1;
            $form['to'] = (new \DateTime())->format('Y-m-d 23:59');
            $form['from'] = (new \DateTime())->format('Y-m-d');
            $form['fromdate'] = (new \DateTime())->format('Y-m-d');
            $form['optionDate'] = 1;
            $form['hour'] = false;
        }
        // Verarbeitung der Case 5 Ereignisse
        if ($request->request->get('mysubmit') === 'yes' || $request->request->get('addCase5') === 'addCase5') {
            if ($request->request->get('addCase5') === 'addCase5') {
                $this->updateCase5Availability(
                    $aktAnlage,
                    $request->request->get('case5id'),
                    $request->request->get('to'),
                    $request->request->get('case5from'),
                    $request->request->get('case5to'),
                    $request->request->get('case5inverter'),
                    $request->request->get('case5reason'),
                    $entityManager,
                    $availabilityService
                );
            }
            $form['selectedChart'] = $request->request->get('selectedChart');
            $form['selectedGroup'] = $request->request->get('selectedGroup');
            $form['selectedInverter'] = $request->request->get('selectedInverter');
            $form['selectedSet'] = $request->request->get('selectedSet');
            $form['optionDate'] = $request->request->get('optionDate');
            $form['optionStep'] = $request->request->get('optionStep');
            $form['fromdate'] = $request->request->get('fromdate');
            $form['backFromMonth'] = false;
            $form['hour'] = $request->request->get('hour');
            // Abfangen des Zeitintervalls und setzt ['optionDate'] neu
            switch ($form['selectedChart']){
                case 'pr_and_av':
                    if ($form['optionDate'] < 7) { $form['optionDate'] = 7; }
                    if ($form['optionDate'] == 300000) { $form['optionDate'] = 7; }
                break;
                case 'sollistanalyse':
                    if ($form['optionDate'] <= 14 ) { $form['optionDate'] = 100000; }
                break;
                case 'sollisttempanalyse':
                    if ($form['optionDate'] <= 14 ) { $form['optionDate'] = 100000; }
                break;
                default:
                    if ($form['selectedChart'] != 'sollistanalyse' or $form['selectedChart'] != 'sollisttempanalyse') {
                        $form['optionDate'] = 1;
                    }
                break;
            }

            // bei Verfügbarkeit Anzeige kann nur ein Tag angezeigt werden
            // if ($form['selectedChart'] == 'availability' && $form['optionDate'] > 1) { $form['optionDate'] = 1; }

            /* Bei Verfügbarkeit des Cahrts in der Anzeige wird zuerst nur ein Tag angezeigt
               optionStep == Single Step des Zeitintervalls der Anzeige im Chart
               optionDate == 1 → Zeige Daten für 1 Tage, also vom ausgewählten Tag zurück 1 Tage
               optionDate == 3 → Zeige Daten für 3 Tage, also vom ausgewählten Tag zurück 3 Tage
               optionDate == 7 → Zeige Daten für 7 Tage, also vom ausgewählten Tag zurück 7 Tage
               optionDate == 14 → Zeige Daten für 14 Monat, also vom ausgewählten Tag zurück 14 Monate
               optionDate == 100000 → Zeige Daten für den ganzen Monat, also vom ersten bis zum letzten Tages eines ausgewähten Monats
               optionDate == 300000 → Zeige Daten für 3 Monat, also vom ausgewählten Tag zurück 3 Monate
            */

            if ($form['optionStep'] == 'lastday' or $form['optionStep'] == 'nextday') {
                switch ($form['optionStep']) {
                    case 'lastday':
                        $getTo = ($request->request->get('to')) ? $request->request->get('to') : date('Y-m-d');
                        switch ($form['optionDate']){
                            case 1 :
                                $from = date('Y-m-d 00:00', strtotime($getTo.'-1 day'));
                                $to = date('Y-m-d 23:59', strtotime($from));
                                break;
                            case 3 :
                                $from = date('Y-m-d 00:00', strtotime($form['fromdate'].'-2 day'));
                                $to = date('Y-m-d 23:59', strtotime($form['fromdate']));
                                break;
                            case 7 :
                                $from = date('Y-m-d 00:00', strtotime($form['fromdate'].'-6 day'));
                                $to = date('Y-m-d 23:59', strtotime($form['fromdate']));
                                break;
                            case 14 :
                                $from = date('Y-m-d 00:00', strtotime($form['fromdate'].'-13 day'));
                                $to = date('Y-m-d 23:59', strtotime($form['fromdate']));
                                break;
                            case 100000 :
                                $from = date('Y-m-d 00:00', strtotime($form['fromdate'].'-1 months'));
                                $to = date("Y-m-t 23:59", strtotime($from));
                                break;
                            case 300000 :
                                $from = date('Y-m-d 00:00', strtotime($form['fromdate'].'-3 months'));
                                $to = date("Y-m-t 23:59", strtotime($from.'+2 months'));
                                break;
                        }
                        $form['from'] = $from;
                        (strtotime($from) > strtotime('now')) ? $form['from'] = date('Y-m-d 00:00') : $form['from'] = $from;
                        (strtotime($to) > strtotime('now')) ? $form['to'] = date('Y-m-d 23:59') : $form['to'] = $to;
                        break;
                    case 'nextday':
                        $getTo = ($request->request->get('to')) ? $request->request->get('to') : date('Y-m-d');
                        switch ($form['optionDate']){
                            case 1 :
                                $to = date('Y-m-d 23:59', strtotime($getTo.'+1 day'));
                                $from = date('Y-m-d 00:00', strtotime($to));
                                break;
                            case 3 :
                                $from = date('Y-m-d 00:00', strtotime($getTo));
                                $to = date('Y-m-d 23:59', strtotime($getTo.'+2 day'));
                                break;
                            case 7 :
                                $from = date('Y-m-d 00:00', strtotime($getTo));
                                $to = date('Y-m-d 23:59', strtotime($getTo.'+6 day'));
                                break;
                            case 14 :
                                $from = date('Y-m-d 00:00', strtotime($getTo));
                                $to = date('Y-m-d 23:59', strtotime($getTo.'+13 day'));
                                break;
                            case 100000 :
                                $from = date('Y-m-01 00:00', strtotime($form['fromdate'].'+1 months'));
                                $to = date("Y-m-t 23:59", strtotime($from));
                                break;
                            case 300000 :
                                $from = date('Y-m-01 00:00', strtotime($getTo.'+1 months'));
                                $to = date("Y-m-t 23:59", strtotime($from.'+2 months'));
                                break;
                        }
                        $form['from'] = $from;
                        (strtotime($from) > strtotime('now')) ? $form['from'] = date('Y-m-d 00:00') : $form['from'] = $from;
                        (strtotime($to) > strtotime('now')) ? $form['to'] = date('Y-m-d 23:59') : $form['to'] = $to;
                        break;
                }
            } else {
                if ($form['optionDate'] == 100000) {
                    $form['from'] = date('Y-m-01 00:00', strtotime($request->request->get('to')));
                    $form['to'] = date("Y-m-t 23:59", strtotime($request->request->get('to')));
                    if (strtotime($form['to']) > strtotime(date('Y-m-d'))) {
                        $form['to'] = date('Y-m-d H:i');
                    }
                } elseif ($form['optionDate'] == 300000) {
                    $date = ($request->request->get('to')) ? $request->request->get('to') : date('Y-m-d');
                    $form['from'] = date('Y-m-01 00:00', strtotime($date.' -3 months'));
                    $form['to'] = date("Y-m-t 23:59", strtotime($date));
                    /* Formel für Quartalsberechnung
                       $current_quarter = ceil(date('n',strtotime($request->request->get('to'))) / 3);
                       $form['from'] = date('Y-m-d 23:59', strtotime(date('Y') . '-' . (($current_quarter * 3) - 2) . '-1'));
                       $form['to'] = date('Y-m-t 00:00', strtotime(date('Y') . '-' . (($current_quarter * 3)) . '-1'));
                    */
                    if (strtotime($form['to']) > strtotime(date('Y-m-d'))) {
                          $form['to'] = date('Y-m-d H:i');
                    }
                } else {
                    $form['to'] = $request->request->get('to');
                    if (strtotime($form['to']) > strtotime(date('Y-m-d'))) {
                        $form['to'] = date('Y-m-d H:i');
                    }
                    // Korriegiertes Datum, wenn diese in der Zukunft liegt
                    $form['from'] = date('Y-m-d 00:00', strtotime($form['to']) - (86400 * ($form['optionDate'] - 1)));
                }
            }
            // ergänze um Uhrzeit
            if (strlen($form['to']) <= 10) {
                $form['to'] = $form['to'].' 23:59';
            }
            // bei den PA und PR Diagramm werden immer mindestens 7 Tage angezeigt
        }
        $content = null;
        $hour = $request->get('hour') == 'on';
        if ($aktAnlage) {
            $content = $chartService->getGraphsAndControl($form, $aktAnlage, $hour);
        }

        $isInTimeRange = self::isInTimeRange();

        return $this->render('dashboardPlants/plantsShow.html.twig', [
            'anlagen' => $anlagen,
            'aktAnlage' => $aktAnlage,
            'form' => $form,
            'content' => $content,
            'isInTimeRange' => $isInTimeRange,
            'hour' => $hour,
        ]);
    }

    /**
     * Speicher bzw Updaten der Case 5 Einträge.
     *
     * @param $case5id
     * @param $date
     * @param $from
     * @param $to
     * @param $inverter
     * @param $reason
     *
     * @throws Exception
     */
    private function updateCase5Availability(Anlage $anlage, $case5id, $date, $from, $to, $inverter, $reason, EntityManagerInterface $em, AvailabilityService $availabilityService)
    {
        $from = date('Y-m-d ', strtotime($date)).$from;
        $to = date('Y-m-d ', strtotime($date)).$to;

        $case5Repository = $em->getRepository(AnlageCase5::class);
        $case5 = $case5Repository->findOneBy(['id' => $case5id]);

        if (!$case5) {
            $case5 = new AnlageCase5();
        }
        $case5
            ->setAnlage($anlage)
            ->setInverter($inverter)
            ->setStampFrom($from)
            ->setStampTo($to)
            ->setReason($reason)
        ;
        $em->persist($case5);
        $em->flush();
        $availabilityService->checkAvailability($anlage, strtotime($date));
    }
}
