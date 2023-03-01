<?php

namespace App\Controller;

use App\Entity\Anlage;
use App\Entity\AnlageCase5;
use App\Entity\User;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service\AvailabilityService;
use App\Service\Charts\HeatmapChartService;
use App\Service\ChartService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\Charts\SollIstHeatmapChartService;


use function _PHPStan_c900ee2af\React\Promise\all;

class DashboardPlantsController extends BaseController
{
    use G4NTrait;

    #[Route(path: '/api/plants/{eignerId}/{anlageId}/{analyse}', name: 'api_dashboard_plant_analsyse', methods: ['GET','POST'])]
    public function analysePlantAPI($eignerId, $anlageId, $analyse, Request $request, AnlagenRepository $anlagenRepository, ChartService $chartService, HeatmapChartService $heatmapChartService,): Response
    {
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

       switch($analyse) {
           case 'availability':

               break;
           case 'pr_and_av':

               break;
           case 'forecast':

               break;
           case 'heatmap':
               $from =  $request->request->get('from');#post
               $to =  $request->request->get('to');
               $content = null;
               if ($aktAnlage) {
                   $dataArray = $heatmapChartService->getHeatmap($aktAnlage, $from, $to);
                   $resultArray['data'] = $dataArray['chart'];
                   $content = $resultArray;
              }
               break;
           case 'tempheatmap':

               break;
           case 'sollistheatmap':

               break;
           case 'sollistanalyse':

               break;
           case 'sollistirranalyse':

               break;
           case 'sollisttempanalyse':

               break;
           default:
               return new Response(null, 204);
       }
        if (is_array($content) or $content) {
            return new JsonResponse($content);
        } else {
            return new Response(null, 204);
        }
    }

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
                // $granted = explode(',', $user->getGrantedList());
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
            $form['optionDate'] = 1;
            $form['optionIrrVal'] = 400;
            $form['hour'] = false;
        }


        if ($request->request->get('mysubmit') === 'yes' || $request->request->get('mysubmit') === 'select') {
            $form['selectedChart']      = $request->request->get('selectedChart');
            $form['selectedGroup']      = $request->request->get('selectedGroup');
            $form['selectedInverter']   = $request->request->get('selectedInverter');
            $form['selectedSet']        = $request->request->get('selectedSet');
            $form['optionDate']         = $request->request->get('optionDate');
            $form['optionStep']         = $request->request->get('optionStep');
            $form['startDateNew']       = $request->request->get('startDateNew');
            $form['selRange']           = $request->request->get('selRange');
            $form['optionIrrVal']       = $request->request->get('optionIrrVal');
            $form['hour']               = $request->request->get('hour');


            if ($form['selectedChart'] == 'sollistirranalyse'   && !$form['optionIrrVal']) $form['optionIrrVal'] = 400;

            // Predefine Optiondate
            if ($form['selectedChart'] == 'pr_and_av'           && $form['optionDate'] < 7) $form['optionDate'] = 7;

        ##    if ($form['selectedChart'] == 'sollistanalyse'      && $form['optionDate'] < 7) $form['optionDate'] = 100000;
        ##    if ($form['selectedChart'] == 'sollisttempanalyse'  && $form['optionDate'] < 7) $form['optionDate'] = 100000;
        ##    if ($form['selectedChart'] == 'sollistirranalyse'   && $form['optionDate'] < 7) {
        ##        $form['optionDate'] = 100000;
        ##        $form['optionIrrVal'] = 400;
        ##    }
        ##    // bei Verfügbarkeit der Anzeige kann nur ein Tag gezeigt werden
        ##   // if ($form['selectedChart'] == 'availability' && $form['optionDate'] > 1) { $form['optionDate'] = 1; }

            if ($request->request->get('mysubmit') === 'select') {
                $form['to'] = (new \DateTime())->format('Y-m-d 23:59');
                $form['from'] = (new \DateTime())->format('Y-m-d');
            } else {
                if ($form['startDateNew']) {
                    $form['from'] = date('Y-m-d 00:00', strtotime($request->request->get('from')));
                    $form['to'] = date('Y-m-d 23:59', strtotime($request->request->get('to')));
                } else {
                    switch ($form['optionStep']) {
                        case 'lastday':
                            $date = ($request->request->get('to')) ? $request->request->get('to') : date('Y-m-d');
                            if ($form['optionDate'] <= 14) {
                                $to = date('Y-m-d 23:59', strtotime($date) - (86400 * $form['optionDate']));
                                $from = date('Y-m-d 00:00', strtotime($to) - (86400 * ($form['optionDate'] - 1)));
                            }
                            if ($form['optionDate'] == 100000) {
                                $from = date('Y-m-d 00:00', strtotime($date . '-1 month'));
                                $from = date('Y-m-d 00:00', strtotime($from . 'first day of this month'));
                                $to = date('Y-m-d 23:59', strtotime($from . 'last day of this month'));
                            }
                            if ($form['optionDate'] == 300000) {
                                $from = date('Y-m-d 00:00', strtotime($date . '-3 month'));
                                $from = date('Y-m-d 00:00', strtotime($from . 'first day of this month'));
                                $ddate = date('Y-m-d 00:00', strtotime($from . '+3 month'));
                                $to = date('Y-m-d 23:59', strtotime($ddate . 'last day of this month'));
                            }
                            $form['from'] = $from;
                            $form['to'] = strtotime($to) > strtotime('now') ? date('Y-m-d H:i') : $to;
                            break;

                        case 'nextday':
                            $date = ($request->request->get('to')) ? $request->request->get('to') : date('Y-m-d');

                            if ($form['optionDate'] <= 14) {
                                $to = date('Y-m-d 23:59', strtotime($date) + (86400 * $form['optionDate']));
                                $from = date('Y-m-d 00:00', strtotime($to) - (86400 * ($form['optionDate'] - 1)));
                            }
                            if ($form['optionDate'] == 100000) {
                                $from = date('Y-m-d 00:00', strtotime($date . '+1 month'));
                                $from = date('Y-m-d 00:00', strtotime($from . 'first day of this month'));
                                $to = date('Y-m-d 00:00', strtotime($from . 'last day of this month'));
                            }
                            if ($form['optionDate'] == 300000) {
                                $ndate = date('Y-m-d 00:00', strtotime($date . '+3 month'));
                                $from = date('Y-m-d 00:00', strtotime($date . 'first day of this month'));
                                $to = date('Y-m-d 00:00', strtotime($ndate . 'last day of this month'));
                            }
                            $form['from'] = $from;
                            $form['to'] = strtotime($to) > strtotime('now') ? date('Y-m-d H:i') : $to;
                            break;

                        default:
                            // optionDate == 100000 → Zeige Daten für den ganzen Monat, also vom ersten bis zum letzten Tages des ausgewäten Monats
                            if ($form['optionDate'] == 100000) {
                                $daysInMonth = date('t', strtotime($request->request->get('to')));
                                $form['to'] = date("Y-m-$daysInMonth 23:59", strtotime($request->request->get('to')));
                                $form['from'] = date('Y-m-01 00:00', strtotime($request->request->get('to')));
                            } elseif ($form['optionDate'] == 300000) {
                                $ndate = date('Y-m-d 00:00', strtotime($request->request->get('to') . '-3 month'));
                                $form['from'] = date('Y-m-d 00:00', strtotime($ndate . 'first day of this month'));
                                $form['to'] = date('Y-m-d 00:00', strtotime($request->request->get('to') . 'first day of this month'));
                                #quartals berechnung php mit ceil()
                                #$current_quarter = ceil(date('n',strtotime($request->request->get('to'))) / 3);
                                #$form['from'] = date('Y-m-d 23:59', strtotime(date('Y') . '-' . (($current_quarter * 3) - 2) . '-1'));
                                #$form['to'] = date('Y-m-t 00:00', strtotime(date('Y') . '-' . (($current_quarter * 3)) . '-1'));

                            } else {
                                $form['to'] = $request->request->get('to');
                                if ($form['to'] > date('Y-m-d')) {
                                    $form['to'] = date('Y-m-d H:i');
                                }
                                // Korrigiert Datum, wenn diese in der Zukunft liegt
                                $form['from'] = date('Y-m-d 00:00', strtotime($form['to']) - (86400 * ($form['optionDate'] - 1)));
                            }
                    }
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
     * @param Anlage $anlage
     * @param $case5id
     * @param $date
     * @param $from
     * @param $to
     * @param $inverter
     * @param $reason
     * @param EntityManagerInterface $em
     * @param AvailabilityService $availabilityService
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
