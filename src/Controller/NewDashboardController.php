<?php

namespace App\Controller;


use App\Repository\AnlagenRepository;
use App\Repository\EignerRepository;
use App\Service\SystemStatus2;
use DateTime;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;


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

                $last_7_days_tickets=[
                    "total" => $plant["last_7_days_tickets_total"],
                    "status_10" => ['s'=>(int)$plant["last_7_days_tickets_status_10"],'alerts'=>(string)$plant["last_7_days_tickets_status_10_ids"]],
                    "status_30" => ['s'=>(int)$plant["last_7_days_tickets_status_30"],'alerts'=>(string)$plant["last_7_days_tickets_status_30_ids"]],
                    "status_40" => ['s'=>(int)$plant["last_7_days_tickets_status_40"],'alerts'=>(string)$plant["last_7_days_tickets_status_40_ids"]],
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
                'last_7_days_tickets'=>json_encode($last_7_days_tickets),
                'mro'=>json_encode($mro)


            ];
        }
        $data['plants']=  $jsonContent;
        $data['isG4n']=  $this->isGranted('ROLE_G4N');


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
        if ($ioPlantDataStatus === 'alert' || $ioWeatherDataStatus === 'alert' || $expDiffStatus === 'alert' || $paTodayStatus === 'alert') {
            return ['color'=>'red','status'=>'Alert'];
        }

        // Check conditions for "warning"
        if ($ioPlantDataStatus === 'warning' || $ioWeatherDataStatus === 'warning' || $expDiffStatus === 'warning' || $paTodayStatus === 'warning') {
            return ['color'=>'orange','status'=>'Warning'];
        }

        // Check conditions for "blue"
        if ($ioPlantDataStatus === '' || $ioWeatherDataStatus === '' || $expDiffStatus === '' || $paTodayStatus === '') {
            return ['color'=>'blue','status'=>'No data'];
        }

        // If all are "normal", return "green"
        if ($ioPlantDataStatus === 'normal' && $ioWeatherDataStatus === 'normal' && $expDiffStatus === 'normal' && $paTodayStatus === 'normal') {
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
