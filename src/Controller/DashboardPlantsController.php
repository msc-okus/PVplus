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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DashboardPlantsController extends BaseController
{
    use G4NTrait;

    /**
     * @Route("/dashboard/plants/{eignerId}/{anlageId}", name="app_dashboard_plant")
     */
    public function index($eignerId, $anlageId, Request $request, AnlagenRepository $anlagenRepository, ChartService $chartService, EntityManagerInterface $entityManager, AvailabilityService $availabilityService)
    {
        $form = [];
        /** @var Anlage|null $aktAnlage */
        if ($anlageId && $anlageId > 0) {
            $aktAnlage = $anlagenRepository->findOneBy(['anlId' => $anlageId]);
        } else {
            $aktAnlage = null;
        }
        /** @var Anlage $anlagen */
        if ($eignerId) {
            if ($this->isGranted('ROLE_G4N')) {
                $anlagen = $anlagenRepository->findByEignerActive($eignerId, $anlageId);
            } else {
                /* @var User $user */
                $user = $this->getUser();
                $granted = explode(',', $user->getGrantedList());
                $anlagen = $anlagenRepository->findGrantedActive($eignerId, $anlageId, $granted);
            }
        }
        if ($request->request->get('mysubmit') === null || $request->request->all() === null) {
            $form['selectedChart'] = 'ac_single';
            $form['selectedGroup'] = 1;
            $form['selectedInverter'] = 1;
            $form['selectedSet'] = 1;
            $form['to'] = (new \DateTime)->format("Y-m-d 23:59");
            $form['from'] = (new \DateTime)->format("Y-m-d");
            $form['optionDate'] = 1;
        }

        // Verarbeitung der Case 5 Ereignisse
        if ($request->request->get('mysubmit') == 'yes' || $request->request->get('addCase5') === 'addCase5') {
            $currentYear = date("Y");
            $currentMonth = date("m");
            $currentDay = date("d");
            if ($request->request->get('addCase5') === 'addCase5'){
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
            $form['backFromMonth'] = false;

            if($form['optionDate'] == 100000){
                $_SESSION['currentMonth'] = true;
                $_SESSION['lastFormFrom'] = date("Y-m-d 00:00", strtotime($request->request->get('to')) - (86400 * ($_SESSION['optionDate'] - 1)));
                $_SESSION['lastFormTo'] = date("Y-m-d 23:59", strtotime(date("Y-m-d", strtotime($request->request->get('to')))));
                $daysInMonth = cal_days_in_month(CAL_GREGORIAN, date("m", strtotime($request->request->get('to'))), date("Y", strtotime($request->request->get('to'))));
                $form['to'] = date("Y-m-d 23:59", strtotime(date("Y-m", strtotime($request->request->get('to'))).'-'.$daysInMonth));
            }else{
                if($_SESSION['currentMonth']){
                    #dd($_SESSION['lastFormFrom']);
                    $form['backFromMonth'] = true;
                    $form['to'] =  $_SESSION['lastFormTo'];
                }else{
                    $form['to'] =  $request->request->get('to');
                }

            }


            if ( strlen($form['to']) <= 10 ) {$form['to'] = $form['to'] . " 23:59"; } // ergänze um Uhrzeit
            if($form['selectedChart'] == 'pr_and_av'    && $form['optionDate'] < 7) { $form['optionDate'] =  '7'; }
            if($form['selectedChart'] == 'availability' && $form['optionDate'] > 1) { $form['optionDate'] =  '1'; }

            if($form['optionDate'] == 100000){
                #dd(date("Y-m-d 00:00", strtotime($request->request->get('to')) - (86400 * (1 - 1))));
                $form['from'] = date("Y-m-d 00:00", strtotime(date("Y-m", strtotime($request->request->get('to')))));
            }else{
                if($form['backFromMonth']){
                    #dd('gdfgdfgdf');
                    $form['from'] =  $_SESSION['lastFormFrom'];
                    $_SESSION['currentMonth'] = false;
                    $form['backFromMonth'] = false;
                }else{

                    $form['from'] = date("Y-m-d 00:00", strtotime($request->request->get('to')) - (86400 * ($form['optionDate'] - 1)));
                }

            }
        }

        $_SESSION['optionDate'] = $form['optionDate'];

        $content = null;
        if ($aktAnlage) $content = $chartService->getGraphsAndControl($form, $aktAnlage);
        $isInTimeRange = self::isInTimeRange();

        return $this->render('dashboardPlants/plantsShow.html.twig', [
            'anlagen'       => $anlagen,
            'aktAnlage'     => $aktAnlage,
            'form'          => $form,
            'content'       => $content,
            'isInTimeRange' => $isInTimeRange,
        ]);
    }

    /**
     * Speicher bzw Updaten der Case 5 Einträge
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
     */
    private function updateCase5Availability(Anlage $anlage, $case5id, $date, $from, $to, $inverter, $reason, EntityManagerInterface $em, AvailabilityService $availabilityService)
    {
        $from = date('Y-m-d ', strtotime($date)) . $from;
        $to   = date('Y-m-d ', strtotime($date)) . $to;

        $case5Repository = $em->getRepository(AnlageCase5::class);
        $case5 = $case5Repository->findOneBy(['id'=> $case5id,]);

        if(! $case5) $case5 = new AnlageCase5();
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
