<?php

namespace App\Controller;

use App\Entity\Eigner;
use App\Repository\AnlagenRepository;
use App\Repository\EignerRepository;
use App\Repository\UserRepository;
use DateTime;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Serializer\SerializerInterface;
use function Symfony\Component\String\s;

class NewDashboardController extends BaseController
{
    #[Route(path: '/new', name: 'app_newDashboard')]
    public function index(): Response
    {

        return $this->render('newDashboardAdmin/eignerShow.html.twig');
    }

    #[Route(path: '/new/retrieve_plants', name: 'app_newDashboard_retrieve_plants')]
    public function index2(EignerRepository $eignerRepository, AnlagenRepository $anlagenRepository, SerializerInterface $serializer): JsonResponse
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


                $statusData = [
                    'ioPlantStatus' => $status?$status->getLastDataStatus():'',
                    'ioWeatherStatus' => $status?$status->getLastWeatherStatus():'',
                    'acDiffStatus' => $status?$status->getAcDiffStatus():'',
                    'dcDiffStatus' => $status?$status->getDcDiffStatus():'',
                    'invStatus' => $status?$status->getInvStatus():'',
                    'dcStatus' => $status?$status->getDcStatus():'',
                ];


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
                    "status_10" => (int)$plant["last_7_days_tickets_status_10"],
                    "status_30" => (int)$plant["last_7_days_tickets_status_30"],
                    "status_40" => (int)$plant["last_7_days_tickets_status_40"],
                    "status_90" => (int)$plant["last_7_days_tickets_status_90"]
                ];





            $jsonContent[] = [
                'id' => $plant[0]->getAnlId(),
                'name' => $plant[0]->getAnlName(),
                'country' => $plant[0]->getCountry(),
                'status' =>$this->getStatusColor($statusData),
                'statusData' =>json_encode($statusData),
                'firma' => $plant['firma'],
                'anlBetrieb'=> $this->convertDateTimeToString($plant[0]->getAnlBetrieb()),
                'pnom'=> number_format($plant[0]->getPnom(),1,',','.'),
                'pr_act'=> json_encode($performanceAct),
                'pr_exp'=> json_encode($performanceExp),
                'pr_yesterday' => json_encode($performanceYesterday),
                'pr_year'=>json_encode($performanceYear),
                'last_7_days_tickets'=>json_encode($last_7_days_tickets)
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



    private function getStatusColor(array $statusData): string
    {

        if (empty($statusData['ioPlantStatus']) && empty($statusData['ioWeatherStatus']) &&
            empty($statusData['acDiffStatus']) && empty($statusData['dcDiffStatus']) &&
            empty($statusData['invStatus']) && empty($statusData['dcStatus'])) {
            return 'blue';
        }


        if ($statusData['ioPlantStatus'] === 'alert' || $statusData['ioWeatherStatus'] === 'alert' ||
            $statusData['acDiffStatus'] === 'alert' || $statusData['dcDiffStatus'] === 'alert' ||
            $statusData['invStatus'] === 'alert' || $statusData['dcStatus'] === 'alert') {
            return 'red';
        }


        $allowedValues = ['normal', 'warning', ''];
        if (
            ($statusData['ioPlantStatus'] === 'normal' && $statusData['ioWeatherStatus'] === 'normal') &&
            (in_array($statusData['acDiffStatus'], $allowedValues, true) && in_array($statusData['dcDiffStatus'], $allowedValues, true)) &&
            (in_array($statusData['invStatus'], $allowedValues, true) && in_array($statusData['dcStatus'], $allowedValues, true))
        ) {
            return 'green';
        }


        foreach ($statusData as $value) {
            if ($value !== 'normal' && $value !== '') {
                return 'red';
            }
        }

        return 'green';
    }
}
