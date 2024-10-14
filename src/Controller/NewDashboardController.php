<?php

namespace App\Controller;


use App\Entity\Anlage;
use App\Repository\AnlagenRepository;
use App\Service\Charts\ChartService;
use App\Service\SystemStatus2;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


class NewDashboardController extends BaseController
{
    #[Route(path: '/new', name: 'app_newDashboard')]
    public function index(): Response
    {

        return $this->render('newDashboardAdmin/eignerShow.html.twig');
    }

    #[Route(path: '/new/retrieve_plants', name: 'app_newDashboard_retrieve_plants')]
    public function index2( AnlagenRepository $anlagenRepository, SystemStatus2 $systemStatus2): JsonResponse
    {
        $user = $this->getUser();
        $grantedString = $user->getGrantedList();
        $grantedArray = !empty(trim($grantedString)) ? explode(',', trim($grantedString)) : [];


        if ($this->isGranted('ROLE_G4N')) {
            $plants = $anlagenRepository->findPlantsForDashboard();
        } else {
            $plants = $anlagenRepository->findPlantsForDashboardForUserWithGrantedList($grantedArray, $user);
        }




        $jsonContent = [];
        foreach ($plants as $plant) {

            $status = $plant[0]->getLastStatus()[0] ?? null;
            $pr = $plant[0]->getYesterdayPR()[0] ?? null;
            $mro= $this->countAndGroupElements($plant['mro']);

             $statusData= $systemStatus2->systemstatus($plant[0]);


                $performanceAct=[
                   'lastDataIo'=> $status?$this->convertDateTimeToHourString($status->getLastDataIo()):'',
                    'acActAll'=>$status? number_format($status->getAcActAll(),0,',','.'):'',
                ];
                $performanceExp=[
                    'lastDataIo'=> $status?$this->convertDateTimeToHourString($status->getLastDataIo()):'',
                    'acExpAll'=>$status? number_format($status->getAcExpAll(),0,',','.'):''
                ];


                $powerAct=$pr?number_format($pr->getPowerAct(),1,',','.'):'';
                $powerExp=$pr?number_format($pr->getPowerExp(),1,',','.'):'';
                $performanceYesterday=[
                    'prYesterday'=>$powerAct,
                    'prYesterdayExp'=>$powerExp,
                ];

                $powerEvuYear=$pr?number_format($pr->getPowerEvuYear(),0,',','.'):'';
                $powerActYear=$pr?number_format($pr->getPowerActYear(),0,',','.'):'';
                $performanceYear=[
                    'power'=> $plant[0]->getShowEvuDiag()?$powerEvuYear: $powerActYear,

                ];

                $tickets=[
                    "total" => $plant["tickets_status_sum"],
                    "status_10" => ['s'=>(int)$plant["tickets_status_10"],'alerts'=>(string)$plant["tickets_status_10_ids"]],
                    "status_30" => ['s'=>(int)$plant["tickets_status_30"],'alerts'=>(string)$plant["tickets_status_30_ids"]],
                    "status_40" => ['s'=>(int)$plant["tickets_status_40"],'alerts'=>(string)$plant["tickets_status_40_ids"]],
                    "status_90" => ['s'=>(int)$plant["last_7_days_tickets_status_90"],'alerts'=>(string)$plant["last_7_days_tickets_status_90_ids"]]
                ];


            $jsonContent[] = [
                'id' => $plant[0]->getAnlId(),
                'name' => $plant[0]->getAnlName(),
                'country' => $plant[0]->getCountry(),
                'status' =>json_encode($this->getStatusColor($statusData)),
                'statusData' =>json_encode($statusData),
                'firma' => $plant['firma'],
                'anlBetrieb'=> $this->convertDateTimeToString($plant[0]->getAnlBetrieb()),
                'pnom'=> number_format($plant[0]->getPnom(),1,',','.'),
                'pr_act'=> json_encode($performanceAct),
                'pr_exp'=> json_encode($performanceExp),
                'pr_yesterday' => json_encode($performanceYesterday),
                'pr_year'=>json_encode($performanceYear),
                'tickets'=>json_encode($tickets),
                'mro'=>json_encode($mro)


            ];
        }
        $data['plants']=  $jsonContent;
        $data['isG4n']=  $this->isGranted('ROLE_G4N');


        return $this->json($data, 200, ['Content-Type' => 'application/json']);
    }

    #[Route(path: '/new/chart', name: 'app_newDashboard_chart')]
    public function chart( Request $request,ChartService $chartService,AnlagenRepository $anlagenRepository): JsonResponse{

        // Decode the JSON content
        $content = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        // Extract the values from the decoded JSON
        $hour = $content['toggleOption'] ;
        $anlageId = $content['anlageId'] ;
        $selectedChart = $content['selectedChart'] ;
        $startDate = $content['startDate'] ;
        $endDate = $content['endDate'] ;



        $form = [
            'optionDate' => 1,
            'from' => date('Y-m-d 00:00', strtotime($startDate)),
            'to' => date('Y-m-d 23:59', strtotime($endDate)),
            'hour' => $hour,
            'selectedChart' => $selectedChart
            // Add other form data you expect
        ];


        $plant=[];
        /* @var Anlage|null $aktAnlage */
        if ($anlageId && $anlageId > 0) {
            $aktAnlage = $anlagenRepository->findOneBy(['anlId' => $anlageId]);
        } else {
            $aktAnlage = null;
        }
        if ($aktAnlage) {
            $plant = $chartService->getGraphsAndControlAcDC($form, $aktAnlage, $hour);
        }
        $sumContent=[

             'actSum'=> $plant['actSum'],
              'expSum' => $plant['expSum'],
              'evuSum'=> $plant['evuSum'],
              'expEvuSum' => $plant['expEvuSum'],
              'expNoLimitSum' => $plant['expNoLimitSum'],
              'irrSum' => $plant['irrSum'],
              'cosPhiSum' => $plant['cosPhiSum'],
              'headline' => $plant['headline'],
             'theoPowerSum' => $plant['theoPowerSum'],
        ];


        $data[]=[
            'data'=>$plant['data'],
            'sum'=>json_encode($sumContent)

        ];

        return $this->json($data, 200, ['Content-Type' => 'application/json']);
    }


    private function convertDateTimeToString(?DateTime $date):string {

        if ($date === null) {
            return '';
        }

        return $date->format('Y-m-d');
    }

    private function convertDateTimeToHourString(?DateTime $date):string {

        if ($date === null) {
            return '';
        }

        return $date->format('H:i');
    }



    private function getStatusColor(array $statusData): array {
        // Extract the relevant status values
        $ioPlantDataStatus = $statusData['ioPlantData']['lastDataStatus'] ?? '';
        $ioWeatherDataStatus = $statusData['ioWeatherData']['lastDataStatus'] ?? '';
        $expDiffStatus = $statusData['expDiff']['expDiffStatus'] ?? '';
        $paTodayStatus = $statusData['paToday']['paStatus'] ?? '';

        // Check conditions for "alert"
        if ($ioPlantDataStatus === 'alert' || $ioWeatherDataStatus === 'alert'  || $paTodayStatus === 'alert') {
            return ['color'=>'red','status'=>'Alert'];
        }

        // Check conditions for "warning"
        if ($ioPlantDataStatus === 'warning' || $ioWeatherDataStatus === 'warning'  || $paTodayStatus === 'warning') {
            return ['color'=>'orange','status'=>'Warning'];
        }

        // Check conditions for "blue"
        if ($ioPlantDataStatus === '' || $ioWeatherDataStatus === '' ||  $paTodayStatus === '') {
            return ['color'=>'blue','status'=>'No data'];
        }

        // If all are "normal", return "green"
        if ($ioPlantDataStatus === 'normal' && $ioWeatherDataStatus === 'normal'  && $paTodayStatus === 'normal') {
            return ['color'=>'green','status'=>'Normal'];
        }

        // Default return value if none of the above conditions match
        return ['color'=>'black','status'=>'Unknown'];
    }


    private function countAndGroupElements(array $data): array
    {
        $result = [];

        $result['total']= count($data);
        // Iterate over the data and count/group the elements
        foreach ($data as $key => $value) {
            if (isset($result[$value])) {
                $result[$value]['zahl']++;
                $result[$value]['alerts'] .= ',' . $key;
            } else {
                $result[$value]['zahl'] = 1;
                $result[$value]['alerts'] = $key;
            }
        }


        return $result;
    }
}
