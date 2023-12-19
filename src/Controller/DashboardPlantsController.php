<?php

namespace App\Controller;

use App\Entity\Anlage;
use App\Entity\User;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service\Charts\HeatmapChartService;
use App\Service\ChartService;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class DashboardPlantsController extends BaseController
{
    use G4NTrait;

    /**
     * @throws InvalidArgumentException
     */
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
               return new Response(null, \Symfony\Component\HttpFoundation\Response::HTTP_NO_CONTENT);
        }
        if (is_array($content) or $content) {
            return new JsonResponse($content);
         } else {
            return new Response(null, \Symfony\Component\HttpFoundation\Response::HTTP_NO_CONTENT);
        }
    }
    /**
     * @throws Exception
     */
    #[Route(path: '/dashboard/plants/{eignerId}/{anlageId}', name: 'app_dashboard_plant')]
    public function index($eignerId, $anlageId, Request $request, AnlagenRepository $anlagenRepository, ChartService $chartService): Response
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
            $form['optionDate'] = 1;
            $form['optionIrrVal'] = 400;
            $form['hour'] = false;
            $form['selRange'] = $request->request->get('selRange');
        }

        if ($request->request->get('mysubmit') === 'yes' || $request->request->get('mysubmit') === 'select') {
            $form['selectedChart']      = $request->request->get('selectedChart');
            $form['selectedGroup']      = $request->request->get('selectedGroup');
            $form['selectedInverter']   = $request->request->get('selectedInverter');
            $form['selectedSet']        = $request->request->get('selectedSet');
            $form['startDateNew']       = $request->request->get('startDateNew');
            $form['selRange']           = $request->request->get('selRange');
            $form['optionIrrVal']       = $request->request->get('optionIrrVal');
            $form['hour']               = $request->request->get('hour');
            if ($form['selectedChart'] == 'sollistirranalyse'   && !$form['optionIrrVal']) $form['optionIrrVal'] = 400;
            if ($form['selectedChart'] == 'pr_and_av'           && $form['optionDate'] < 7) $form['optionDate'] = 7;

            if ($request->request->get('mysubmit') === 'select') {
                /* New: Fix for not leaving the date unless you change the plant */
                if ($form['selectedChart'] == 'heatmap'
                    or $form['selectedChart'] == 'tempheatmap'
                    or $form['selectedChart'] == 'sollistheatmap'
                    or $form['selectedChart'] == 'sollisttempanalyse'
                    or $form['selectedChart'] == 'sollistanalyse'
                    or $form['selectedChart'] == 'sollistirranalyse'
                    or $form['selectedChart'] == 'acpnom') {

                    $form['from'] = date('Y-m-d 00:00', strtotime($request->request->get('from')));
                    $form['to'] = date('Y-m-d 23:59', strtotime($request->request->get('to')));

                    } else {

                    $date1 = strtotime(date('Y-m-d', strtotime($request->request->get('from'))));
                    $date2 = strtotime(date('Y-m-d ', strtotime($request->request->get('to'))));
                    $datediff = abs(round(($date1 - $date2) / (60 * 60 * 24)));

                    if ($datediff > 31) {
                        $form['from'] = (new \DateTime())->format('Y-m-d 00:00');
                        $form['to'] = (new \DateTime())->format('Y-m-d 23:59');
                      } else {
                        $form['from'] = date('Y-m-d 00:00', strtotime($request->request->get('from')));
                        $form['to'] = date('Y-m-d 23:59', strtotime($request->request->get('to')));
                    }

                }

               } else {

                if ($form['startDateNew']) {
                    $form['from'] = date('Y-m-d 00:00', strtotime($request->request->get('from')));
                    $form['to'] = date('Y-m-d 23:59', strtotime($request->request->get('to')));
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


}