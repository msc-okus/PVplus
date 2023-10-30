<?php

namespace App\Controller;

use App\Form\ImportTools\ImportToolsFormType;
use App\Form\Model\ImportToolsModel;
use App\Helper\G4NTrait;
use App\Helper\ImportFunctionsTrait;
use App\Message\Command\ImportData;
use App\Repository\AnlagenRepository;
use App\Service\ImportService;
use App\Service\LogMessagesService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

class ImportToolsController extends BaseController
{
    use ImportFunctionsTrait;
    use G4NTrait;

    /**
     * @throws \Exception
     */
    #[Route('admin/import/tools', name: 'app_admin_import_tools')]
    public function importManuel(Request $request, MessageBusInterface $messageBus, LogMessagesService $logMessages, AnlagenRepository $anlagenRepo, EntityManagerInterface $entityManagerInterface, ImportService $importService): Response
    {

        //Wenn der Import aus dem Backend angestoßen wird
        $form = $this->createForm(ImportToolsFormType::class);
        $form->handleRequest($request);

        $output = '';
        $start = true;
        // Wenn Calc gelickt wird mache dies:&& $form->get('calc')->isClicked() $form->isSubmitted() &&
        if ($form->isSubmitted() && $form->isValid() && $form->get('calc')->isClicked() && $request->getMethod() == 'POST') {
            /* @var ImportToolsModel $importToolsModel */
            $importToolsModel = $form->getData();
            $importToolsModel->endDate = new \DateTime($importToolsModel->endDate->format('Y-m-d 23:59'));
            $importToolsModel->path = $importToolsModel->anlage->getPathToImportScript();
            $importToolsModel->importType = (string)$form->get('importType')->getData();
            // Start recalculation
            if ($form->get('importType')->getData() == null) {
                $output .= 'Please select what you like to import.<br>';
                $start = false;
            }

            if ($importToolsModel->anlage->getHasPPC() != 1 && $importToolsModel->importType == 'api-import-ppc'){
                $output .= 'This plant has not PPC!<br>';
                $start = false;
            }
            if ($start){
                if ($form->get('function')->getData() != null) {
                    switch ($form->get('function')->getData()) {
                        case 'api-import-data':
                            $output = '<h3>Import API Data:</h3>';
                            $job = 'Import API Data('.$importToolsModel->importType.') – from ' . $importToolsModel->startDate->format('Y-m-d H:i') . ' until ' . $importToolsModel->endDate->format('Y-m-d H:i');
                            $job .= " - " . $this->getUser()->getname();
                            $logId = $logMessages->writeNewEntry($importToolsModel->anlage, 'Import API Data', $job);
                            $message = new ImportData($importToolsModel->anlage->getAnlId(), $importToolsModel->startDate, $importToolsModel->endDate, $importToolsModel->path, $importToolsModel->importType, $logId);
                            $messageBus->dispatch($message);
                            $output .= 'Command was send to messenger! Will be processed in background.<br>';
                            break;
                        default:
                            $output .= 'something went wrong!<br>';
                    }

                } else {
                    $output .= 'Please select a function.<br>';
                }
            }
        }
        // Wenn Close geklickt wird mache dies:
        if ($form->isSubmitted() && $form->isValid() && $form->get('close')->isClicked()) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('import_tools/index.html.twig', [
            'importToolsForm' => $form,
            'output' => $output,
        ]);
    }

    /**
     * Cronjob to Import PLants direct by symfony (configured in backend)
     *
     * @return Response
     * @throws NonUniqueResultException
     */
    #[Route('/import/cron', name: 'import_cron')]
    public function importCron(AnlagenRepository $anlagenRepo, ImportService $importService): Response
    {

        //get all Plants for Import via via Cron
        $anlagen = $anlagenRepo->getSymfonyImportPlants();

        $time = time();
        $time -= $time % 900;
        $start = $time - (4 * 3600);
        $end = $time;

        foreach ($anlagen as $anlage) {
            $importService->prepareForImport($anlage, $start, $end);
        }

        return new Response('This is used for import via cron job.', Response::HTTP_OK, ['Content-Type' => 'text/html']);
    }

    /**
     * Manuel Import PLants direct by symfony via URL (configured in backend)
     *
     * @return Response
     * @throws NonUniqueResultException
     */
    #[Route('/import/manuel', name: 'import_manuell')]
    public function importManuell(
        #[MapQueryParameter] int $id,
        #[MapQueryParameter] string $from,
        #[MapQueryParameter] string $to,
        AnlagenRepository $anlagenRepo,
        ImportService $importService): Response
    {
        $fromts = strtotime("$from 00:00:00");
        $tots = strtotime("$to 23:59:00");

        //get all Plants for Import via via Cron
        $anlage = $anlagenRepo->findOneByIdAndJoin($id);

        for ($dayStamp = $fromts; $dayStamp <= $tots; $dayStamp += 24*3600) {

            $from_new = strtotime(date('Y-m-d 00:15', $dayStamp));
            $to_new = strtotime(date('Y-m-d 23:59', $dayStamp));
            $currentDay = date('d', $dayStamp);

            // Proof if date = today, if yes set $to to current DateTime
            if (date('Y', $to_new) == date('Y') && date('m', $to_new) == date('m') && $currentDay == date('d')) {
                $hour = date('H');
                $minute = date('i');
                $to_new = strtotime(date("Y-m-d $hour:$minute"), $to_new);
            }

            $minute = (int)date('i');
            while (($minute >= 28 && $minute < 33) || $minute >= 58 || $minute < 3) {
                sleep(20);
                $minute = (int)date('i');
            }
            $importService->prepareForImport($anlage, $from_new, $to_new);

            sleep(1);
        }


        return new Response('This is used for import via manual Import.', Response::HTTP_OK, ['Content-Type' => 'text/html']);
    }

}