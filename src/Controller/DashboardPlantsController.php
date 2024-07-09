<?php

namespace App\Controller;

use App\Entity\Anlage;
use App\Entity\User;
use App\Helper\G4NTrait;
use App\Repository\AcGroupsRepository;
use App\Repository\AnlagenRepository;
use App\Service\AvailabilityService;
use App\Service\Charts\ChartService;
use App\Service\Charts\HeatmapChartService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\FunctionsService;

class DashboardPlantsController extends BaseController
{
    use G4NTrait;

    /**
     * @throws InvalidArgumentException
     */
    #[Route(path: '/api/plants/{eignerId}/{anlageId}/{analyse}', name: 'api_dashboard_plant_analsyse', methods: ['GET','POST'])]
    public function analysePlantAPI($eignerId, $anlageId, $analyse, Request $request, AnlagenRepository $anlagenRepository, ChartService $chartService, HeatmapChartService $heatmapChartService): Response
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
            if ($this->isGranted('ROLE_OPERATIONS_G4N')) {
                $anlagen = $anlagenRepository->findByEignerActive($eignerId, $anlageId);
            } else {
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
               return new Response(null, Response::HTTP_NO_CONTENT);
        }
        if (is_array($content) or $content) {
            return new JsonResponse($content);
         } else {
            return new Response(null, Response::HTTP_NO_CONTENT);
        }
    }
    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    #[Route(path: '/dashboard/plants/{eignerId}/{anlageId}', name: 'app_dashboard_plant')]
    public function index($eignerId, $anlageId, Request $request, AnlagenRepository $anlagenRepository, ChartService $chartService, EntityManagerInterface $entityManager, AvailabilityService $availabilityService, AcGroupsRepository $acRepo, FunctionsService $functions=null): Response
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
            if ($this->isGranted('ROLE_OPERATIONS_G4N')) {
                $anlagen = $anlagenRepository->findByEignerActive($eignerId, $anlageId);
            } else {
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
            $form['optionDayAheadView'] = 0;    #0=Dashborrd;1=60Min;2=15Min;
            $form['optionDayAheadViewDay'] = 0; #0=6Tage;1=3Tage;2=2Tage;
            $form['hour'] = false;
            $form['selRange'] = $request->request->get('selRange');
            $form['invnames'] = '';
            $form['invids'] = '';
            $form['inverterRadio'] = 1;
            $form['selectallinverters'] = 0;
            $form['togglePaNull'] = false;
            $form['togglePaOne'] = false;
            $form['togglePaTwo'] = false;
            $form['togglePaThree'] = false;
        }

        if ($request->request->get('mysubmit') === 'yes' || $request->request->get('mysubmit') === 'select') {
            $form['selectedChart']      = $request->request->get('selectedChart');
            $form['selectedGroup']      = $request->request->get('selectedGroup');
            $form['invnames']   = $request->request->get('invnames');
            $form['invids']        = $request->request->get('invids');
            $form['startDateNew']       = $request->request->get('startDateNew');
            $form['selRange']           = $request->request->get('selRange');
            $form['optionIrrVal']       = $request->request->get('optionIrrVal');
            $form['optionDayAheadView']  = $request->request->get('optionDayAheadView');
            $form['optionDayAheadViewDay']  = $request->request->get('optionDayAheadViewDay');
            $form['hour']               = $request->request->get('hour');
            $form['inverterRadio'] = $request->request->get('inverterRadio');
            $form['selectallinverters'] = $request->request->get('selectallinverters');

            $form['togglePaNull'] = $request->request->get('togglePaNull');
            $form['togglePaOne'] = $request->request->get('togglePaOne');
            $form['togglePaTwo'] = $request->request->get('togglePaTwo');
            $form['togglePaThree'] = $request->request->get('togglePaThree');

            if ($form['selectedChart'] == 'sollistirranalyse'   && !$form['optionIrrVal']) $form['optionIrrVal'] = 400;
            if ($form['selectedChart'] == 'pr_and_av'           && $form['optionDate'] < 7) $form['optionDate'] = 7;

            if ($request->request->get('mysubmit') === 'select') {
                /* New: Fix for not leaving the date unless you change the plant */
                if ($form['selectedChart'] == 'sollisttempanalyse'
                    or $form['selectedChart'] == 'sollistanalyse'
                    or $form['selectedChart'] == 'sollistirranalyse'
                    or $form['selectedChart'] == 'acpnom') {

                    $form['from'] = date('Y-m-d 00:00', strtotime($request->request->get('from')));
                    $form['to'] = date('Y-m-d 23:59', strtotime($request->request->get('to')));

                    } else {
                    /* Selected Charts are excluded and remain unchanged by data handling */
                    if ($form['selectedChart'] == 'heatmap'
                        or $form['selectedChart'] == 'tempheatmap'
                        or $form['selectedChart'] == 'sollistheatmap') {

                        $form['selRange']  = 'Today';
                        $form['from'] = (new \DateTime())->format('Y-m-d 00:00');
                        $form['to'] = (new \DateTime())->format('Y-m-d 23:59');

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
        $useRadioButtons = 0;
        if($aktAnlage) {
            $configtype = $aktAnlage->getConfigType();
        }

        if(($form['selectedChart'] == 'sollistheatmap' || $form['selectedChart'] == 'dcpnomcurr' || $form['selectedChart'] == 'dc_current_inverter')  && $aktAnlage->getUseNewDcSchema()){
            $gruopsDc = $aktAnlage->getGroupsDc();
            for ($i = 1; $i <= count($gruopsDc); ++$i) {
                $nameArray[$i] = $gruopsDc[$i]['GroupName'];
                $idsArray[$i] = $i;
            }

            $templateForSelection = 'selectstringboxes.html.twig';

            if($form['selectedChart'] == 'dc_current_inverter'){
                $useRadioButtons = 1;
                if($form['inverterRadio'] == null){
                    $form['inverterRadio'] = 1;
                }
            }
        }else{
            if($aktAnlage){
                switch ($configtype) {
                    case 1:
                        $nameArray = $functions->getNameArray($aktAnlage, 'dc');
                        $idsArray = $functions->getIdArray($aktAnlage, 'dc');
                        break;
                    case 3:
                        $nameArray = $functions->getNameArray($aktAnlage, 'dc');
                        $idsArray = $functions->getIdArray($aktAnlage, 'dc');
                        if($form['selectedChart'] == 'dc_act_overview' || $form['selectedChart'] == 'sollistirranalyse' || $form['selectedChart'] == 'dc_act_group' || $form['selectedChart'] == 'sollisttempanalyse' || $form['selectedChart'] == 'sollistanalyse' || $form['selectedChart'] == 'ac_act_frequency' || $form['selectedChart'] == 'ac_act_current' || $form['selectedChart'] == 'acpnom' || $form['selectedChart'] == 'heatmap' || $form['selectedChart'] == 'dc_current_overview' || $form['selectedChart'] == 'tempheatmap' || $form['selectedChart'] == 'ac_act_group' || $form['selectedChart'] == 'ac_act_voltage' || $form['selectedChart'] == 'dc_voltage_1'){
                            $nameArray = $functions->getNameArray($aktAnlage, 'ac');
                            $idsArray = $functions->getIdArray($aktAnlage, 'ac');
                        }
                        break;
                    default:
                        $nameArray = $functions->getNameArray($aktAnlage, 'ac');
                        $idsArray = $functions->getIdArray($aktAnlage, 'ac');
                }
                $trafoArray = $this->getTrafoArray($aktAnlage, $acRepo);

                $templateForSelection = 'selectinverters.html.twig';

                if($form['selectedChart'] == 'dc_act_overview' || $form['selectedChart'] == 'dc_act_group' || $form['selectedChart'] == 'sollistirranalyse' || $form['selectedChart'] == 'sollisttempanalyse' || $form['selectedChart'] == 'sollistanalyse' || $form['selectedChart'] == 'ac_act_frequency' || $form['selectedChart'] == 'ac_act_current' || $form['selectedChart'] == 'dc_current_overview' || $form['selectedChart'] == 'dc_current_inverter' || $form['selectedChart'] == 'ac_act_group' || $form['selectedChart'] == 'ac_act_overview' || $form['selectedChart'] == 'ac_act_voltage' || $form['selectedChart'] == 'dc_voltage_1'){
                    $useRadioButtons = 1;
                    if($form['inverterRadio'] < 1){
                        $form['inverterRadio'] = 1;
                    }
                }
            }
        }

        //bei nbestimmten Diagrammen nach Trafostation selektieren
        if($configtype == 1 && ($form['selectedChart'] == 'dc_act_overview' || $form['selectedChart'] == 'dc_current_overview' || $form['selectedChart'] == 'ac_act_overview' || $form['selectedChart'] == 'dc_voltage_1')){
            unset($nameArray);
            for ($i = 1; $i <= count($trafoArray); ++$i) {
                $nameArray[$i] = "TS $i";
            }
        }

        unset($functions);
        if($_SESSION['selectedChart'] != $form['selectedChart']){
            $clearSelections = 1;
            $form['invnames'] = '';
            $form['inverterRadio'] = 1;
        }
        if ($aktAnlage) {
            $content = $chartService->getGraphsAndControl($form, $aktAnlage, $hour);
        }

        $inverterArray = [];
        $inverterIdsArray = [];

        // I loop over the array with the real names and the array of selected inverters
        // of the inverter to create a 2-dimension array with the real name and the inverters that are selected
        //In this case there will  be none selected

        if($form['invnames'] == ''){
            $form['invnames'] = $content['invNames'];
        }

        foreach ($nameArray as $key => $value){
            $inverterArray[$key]["invName"] = $value;
            $inverterArray[$key]["select"] = "";

            if(str_contains($form['invnames'], $value)){
                $inverterArray[$key]["select"] = "checked";
            }

        }

        foreach ($idsArray as $key => $value){
            $inverterIdsArray[$key]["invId"] = $value;
            if(str_contains($content['temp'], $value) && $form['invnames'] == ''){
                $inverterArray[$key]["select"] = "checked";
            }
        }

        $isInTimeRange = self::isInTimeRange();
        $clearSelections = 0;

        $_SESSION['selectedChart'] = $form['selectedChart'];

        if($form['selectedChart'] == 'sollistirranalyse' || $form['selectedChart'] == 'sollisttempanalyse' || $form['selectedChart'] == 'sollistanalyse'){
            $selectAllInverters = 1;
        }
#echo $form['selectedChart'];
#exit;

        return $this->render('dashboardPlants/plantsShow.html.twig', [
            'anlagen' => $anlagen,
            'aktAnlage' => $aktAnlage,
            'form' => $form,
            'content' => $content,
            'isInTimeRange' => $isInTimeRange,
            'hour' => $hour,
            'invArray'      => $inverterArray,
            "invIdsArray" => $inverterIdsArray,
            'trafoArray' => $trafoArray,
            'edited' => true,
            'templateForSelection' => $templateForSelection,
            'useRadioButtons' => $useRadioButtons,
            'clearSelections' => $clearSelections,
            'configtype' => $configtype,
            'selectAllInverters' => $selectAllInverters
        ]);
    }

    private function getTrafoArray(Anlage $anlage, AcGroupsRepository $acRepo): array{
        $totalTrafoGroups = $acRepo->getAllTrafoNrForInverterSelect($anlage);
        $trafoArray = [];
        foreach ($totalTrafoGroups as $trafoGroup) {
            $trafoGroupNr = $trafoGroup->getTrafoNr();
            $acGroup = $acRepo->findByAnlageTrafoNr($anlage, $trafoGroupNr);
            if ($acGroup != []) {
                if ($anlage->getConfigType() == 3){
                    $trafoArray[$trafoGroupNr]['first'] = $acGroup[0]->getAcGroup();
                    $trafoArray[$trafoGroupNr]['last'] = $acGroup[sizeof($acGroup) - 1]->getAcGroup();
                }
                else {
                    $trafoArray[$trafoGroupNr]['first'] = $acGroup[0]->getUnitFirst();
                    $trafoArray[$trafoGroupNr]['last'] = $acGroup[sizeof($acGroup) - 1]->getUnitLast();
                }
            }
        }
        return $trafoArray;
    }
}
