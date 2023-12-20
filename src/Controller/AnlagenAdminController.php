<?php

namespace App\Controller;
use App\Service\PdoService;

use App\Entity\Anlage;
use App\Entity\AnlageFile;
use App\Entity\EconomicVarNames;
use App\Entity\WeatherStation;
use Doctrine\ORM\NonUniqueResultException;
use League\Flysystem\Filesystem;
use App\Form\Anlage\AnlageAcGroupsFormType;
use App\Form\Anlage\AnlageConfigFormType;
use App\Form\Anlage\AnlageDcGroupsFormType;
use App\Form\Anlage\AnlageFormType;
use App\Form\Anlage\AnlageNewFormType;
use App\Form\Anlage\AnlageSensorsFormType;
use App\Form\Anlage\AnlagePpcsFormType;
use App\Helper\G4NTrait;
use App\Repository\AnlageFileRepository;
use App\Repository\AnlagenRepository;
use App\Repository\AnlageSunShadingRepository;
use App\Repository\EconomicVarNamesRepository;
use App\Service\UploaderHelper;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use League\Flysystem\FilesystemException;
use RecursiveIteratorIterator;
use Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class AnlagenAdminController extends BaseController
{
    use G4NTrait;
    public function __construct(
        private readonly PdoService $pdoService,
        private readonly Filesystem $fileSystemFtp
    )
    {
    }

    #[Route(path: '/admin/anlagen/new', name: 'app_admin_anlagen_new')]
    public function new(EntityManagerInterface $em, Request $request, WeatherStationController $weatherStationController): RedirectResponse|Response
    {
        $form = $this->createForm(AnlageNewFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked())) {
            /** @var Anlage $anlage */
            $anlage = $form->getData();
            $em->persist($anlage);
            $em->flush();
            $anlage->setAnlIntnr('CX'.$anlage->getAnlagenId());

            if ($form->get('WeatherStation')->getViewData() == "") {
                $weatherStation = new WeatherStation();
                $weatherStationController->createWeatherDatabase($anlage->getAnlIntnr());
                $weatherStation->setDatabaseIdent($anlage->getAnlIntnr());
                $weatherStation->setType('custom');
                $weatherStation->setLocation($anlage->getAnlName());
                $anlage->setWeatherStation($weatherStation);

            }
            $em->persist($anlage);
            $em->flush();
            self::createDatabasesForPlant($anlage);
            $this->addFlash('success', 'New Plant created');

            return $this->redirectToRoute('app_admin_anlagen_list');
        }
        if ($form->isSubmitted() && $form->get('close')->isClicked()) {
            $this->addFlash('warning', 'Canceled. No data was saved.');

            return $this->redirectToRoute('app_admin_anlagen_list');
        }

        return $this->render('anlagen/new.html.twig', [
            'anlageForm' => $form,
        ]);
    }


    #[Route(path: '/admin/anlagen/list', name: 'app_admin_anlagen_list')]
    public function list(Request $request, PaginatorInterface $paginator, AnlagenRepository $anlagenRepository): Response
    {
        $q = $request->query->get('qp');
        if ($request->query->get('search') == 'yes' && $q == '') {
            $request->getSession()->set('qp', '');
        }
        if ($q) {
            $request->getSession()->set('qp', $q);
        }
        if ($q == '' && $request->getSession()->get('qp') != '') {
            $q = $request->getSession()->get('qp');
            $request->query->set('qp', $q);
        }
        $queryBuilder = $anlagenRepository->getWithSearchQueryBuilder($q);
        $pagination = $paginator->paginate(
            $queryBuilder, /* query NOT result */
            $request->query->getInt('page', 1), /* page number */
            25                                         /* limit per page */
        );

        return $this->render('anlagen/list.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route(path: '/admin/anlagen/delete/sunshading/{id}/{sadid}/{token}', name: 'app_admin_anlagen_delete_sun_shading')]
    #[IsGranted('ROLE_DEV')]
    public function delete_sunshading_model($id,$sadid, $token, EntityManagerInterface $em, AnlageSunShadingRepository $anlageSunShadingRepository): Response
    {
        if ($this->isCsrfTokenValid('deletesunshadingmodel'.$sadid, $token)) {
            $sunshadding = $anlageSunShadingRepository->find($sadid);
            $em->remove($sunshadding);
            $em->flush();
            $this->addFlash('success', 'Data deleted !.');
        } else {
            $this->addFlash('warning', 'An error was detected');
            return $this->redirectToRoute('app_admin_anlagen_list');
        }

        return $this->redirectToRoute('app_admin_anlagen_edit',['id' => $id]);

    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route(path: '/admin/anlagen/edit/{id}', name: 'app_admin_anlagen_edit')]
    public function edit($id, EntityManagerInterface $em, Request $request, AnlagenRepository $anlagenRepository, UploaderHelper $uploaderHelper ): RedirectResponse|Response
    {
        $anlage = $anlagenRepository->findOneByIdAndJoin($id);
        $form = $this->createForm(AnlageFormType::class, $anlage, [
            'anlagenId' => $id,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked() || $form->get('savecreatedb')->isClicked())) {
            // Forecast Tab Field Check
            if($form['useDayForecast']->getData() === true) {
                $checkfields = true;
                if ($form->get('bezMeridan')->isEmpty()) {
                    $this->addFlash('warning', 'Field Bezugs Meridan fail.');
                    $checkfields = false;
                }
                if ($form->get('modNeigung')->isEmpty()) {
                    $this->addFlash('warning', 'Field Modul Neigung fail.');
                    $checkfields = false;
                }
                if ($form->get('albeto')->isEmpty()) {
                    $this->addFlash('warning', 'Field Albeto fail.');
                    $checkfields = false;
                }
                if ($form->get('modAzimut')->isEmpty()) {
                    $this->addFlash('warning', 'Field Modul Azimut fail.');
                    $checkfields = false;
                }

                if ($checkfields === false){
                    return $this->render('anlagen/edit.html.twig', [
                        'anlageForm' => $form,
                        'anlage' => $anlage,
                    ]);
                }

            }

            $uploadedDatFile = $form['datFilename']->getData();

            if ($uploadedDatFile) {
                $uploadsPath = "/metodat";
                $newFile = $uploaderHelper->uploadAllFile($uploadedDatFile,$uploadsPath,'dat');
                if ($newFile) {
                    $anlage->setDatFilename($newFile);
                }
            }

            $successMessage = 'Plant data saved!';

            if ($form->get('savecreatedb')->isClicked()) {
                if ($this->createDatabasesForPlant($anlage)) {
                    $successMessage = 'Plant data saved and DB created.';
                }
            }

            $em->persist($anlage);
            $em->flush();
            $this->addFlash('success', $successMessage);

            if ($form->get('saveclose')->isClicked()) {
                return $this->redirectToRoute('app_admin_anlagen_list');
            }
        }
        if ($form->isSubmitted() && $form->get('close')->isClicked()) {
            $this->addFlash('warning', 'Canceled. No data was saved.');

            return $this->redirectToRoute('app_admin_anlagen_list');
        }



        return $this->render('anlagen/edit.html.twig', [
            'anlageForm' => $form,
            'anlage' => $anlage,
        ]);
    }

    /**
     * @param $id
     * @param EntityManagerInterface $em
     * @param Request $request
     * @param AnlagenRepository $anlagenRepository
     * @param EconomicVarNamesRepository $ecoNamesRepo
     * @param UploaderHelper $uploaderHelper
     * @param AnlageFileRepository $RepositoryUpload
     * @param Filesystem $fileSystemFtp
     * @param Filesystem $filesystem
     * @return RedirectResponse|Response
     * @throws FilesystemException
     */
    #[Route(path: '/admin/anlagen/editconfig/{id}', name: 'app_admin_anlagen_edit_config')]
    public function editConfig($id, EntityManagerInterface $em, Request $request, AnlagenRepository $anlagenRepository, EconomicVarNamesRepository $ecoNamesRepo, UploaderHelper $uploaderHelper, AnlageFileRepository $RepositoryUpload, Filesystem $fileSystemFtp, Filesystem $filesystem): RedirectResponse|Response
    {
         $upload = new AnlageFile();
        $anlage = $anlagenRepository->find($id);
        $imageuploaded = $RepositoryUpload->findOneBy(['path' => $anlage->getPicture()]);
        if ($imageuploaded != null) {
            $isupload = 'yes';
            if ($fileSystemFtp->fileExists($imageuploaded->getPath())) $tempFile = self::makeTempFiles([$fileSystemFtp->read($imageuploaded->getPath())], $filesystem)[0];
            else $isupload = 'no';
        } else {
            $isupload = 'no';
        }

        $economicVarNames1 = new EconomicVarNames();
        if ($ecoNamesRepo->findByAnlage($id)[0] != null) {
            $economicVarNames1 = $ecoNamesRepo->findByAnlage($id)[0]; // will be used to load and display the already defined names
        }
        $form = $this->createForm(AnlageConfigFormType::class, $anlage, [
            'anlagenId' => $id,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked())) {
            $uploadedFile = $form['picture']->getData();

            if ($uploadedFile != '') {
                $isupload = 'yes';
                $newFile = $uploaderHelper->uploadImageSFTP($uploadedFile, $anlage->getEigner()->getFirma(), $anlage->getAnlName(), 'plant');
                $newFilename = $newFile['newFilename'];
                $mimeType = $newFile['mimeType'];
                $uploadsPath = $newFile['path'];
                $upload->setFilename($newFilename)
                    ->setMimeType($mimeType)
                    ->setPath($uploadsPath)
                    ->setPlant(null)
                    ->setStamp(date('Y-m-d H:i:s'));

                $em->persist($upload);
                $em->flush();
                $anlage->setPicture($uploadsPath);
                //here we update the pic
                $tempFile = self::makeTempFiles([$fileSystemFtp->read($uploadsPath)], $filesystem)[0];
            }
            if ($economicVarNames1 === null) {
                $economicVarNames = new EconomicVarNames();
            } else {
                $economicVarNames = $economicVarNames1;
            }
            $economicVarNames->setparams($anlage, $form->get('var_1')->getData(), $form->get('var_2')->getData(), $form->get('var_3')->getData(), $form->get('var_4')->getData(), $form->get('var_5')->getData(), $form->get('var_6')->getData(), $form->get('var_7')->getData(), $form->get('var_8')->getData(), $form->get('var_9')->getData(), $form->get('var_10')->getData(), $form->get('var_11')->getData(), $form->get('var_12')->getData(), $form->get('var_13')->getData(), $form->get('var_14')->getData(), $form->get('var_15')->getData());

            // TODO: think and work on the switches, they are quite complex!
            $anlage->setEconomicVarNames($economicVarNames);
            $successMessage = 'Plant data saved!';
            $em->persist($anlage);
            $em->flush();
            if ($form->get('save')->isClicked()) {
                     $response = $this->render('anlagen/editconfig.html.twig', [
                        'anlageForm' => $form,
                        'anlage' => $anlage,
                        'econames' => $economicVarNames1,
                        'isupload' => $isupload,
                        'imageuploadet' => $tempFile,
                    ]);

            }
            if ($form->get('saveclose')->isClicked()) {
                $this->addFlash('success', $successMessage);

                return $this->redirectToRoute('app_admin_anlagen_list');
            }

        }
        if ($form->isSubmitted() && $form->get('close')->isClicked()) {
            $this->addFlash('warning', 'Canceled. No data was saved.');
            return $this->redirectToRoute('app_admin_anlagen_list');

        }
        if (!$form->isSubmitted() || !$form->isValid())$response =  $this->render('anlagen/editconfig.html.twig', [
                'anlageForm' => $form,
                'anlage' => $anlage,
                'econames' => $economicVarNames1,
                'isupload' => $isupload,
                'imageuploadet' => $tempFile,
        ]);
        return $response;
    }

    /**
     * @throws FilesystemException
     */
    #[Route(path: '/admin/anlagen/download/{id}/{dir}/{file}/{ext}', name: 'download_file', methods: ['GET','POST'])]
    public function downloadFile($id, $dir, $file, $ext, AnlagenRepository $anlagenRepository, KernelInterface $kernel): BinaryFileResponse|Response
    {
        $anlage = $anlagenRepository->find($id);
        $form = $this->createForm(AnlageDcGroupsFormType::class, $anlage, [
            'anlagenId' => $id,
        ]);

        $filename = $file.'.'.$ext;
        $ftplink = $dir.'/'.$file.'.'.$ext;

        if ($this->fileSystemFtp->fileExists($ftplink)) {

            $resource = $this->fileSystemFtp->readStream($ftplink);
            $metadata = stream_get_meta_data($resource);
            $resourcedata = $this->fileSystemFtp->read($ftplink);
            $path = $metadata['uri']; // alternative

            $tmpfile = tempnam(sys_get_temp_dir(), '~g4n'); // Erstellt ein Tmp file
            $handle = fopen($tmpfile, "w");
            fwrite($handle,  $resourcedata);
            fclose($handle);

            $response = new BinaryFileResponse($tmpfile);
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);

            $this->addFlash('success', 'File was downloaded.');
            return $response;
        } else {
            $this->addFlash('warning', 'Sorry file not found.');
            return $this->render('anlagen/edit.html.twig', [
                'anlageForm' => $form,
                'anlage' => $anlage,
            ]);
        }

    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/admin/anlagen/buildforcast/{id}', name: 'app_admin_anlagen_build_forecast', methods: ['GET','POST'])]
    public function buildForcast($id, KernelInterface $kernel): RedirectResponse|Response
    {
        $response = new Response();
        $response->setStatusCode(Response::HTTP_OK);

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput(['command' => 'pvp:forcastwritedb', '-a'  => $id]);

        $output = new BufferedOutput();
        $application->run($input, $output);

        return $response;
    }

    #[Route(path: '/admin/anlagen/editdcgroups/{id}', name: 'app_admin_anlagen_edit_dcgroups')]
    public function editDcGroups($id, EntityManagerInterface $em, Request $request, AnlagenRepository $anlagenRepository): RedirectResponse|Response
    {
        $anlage = $anlagenRepository->find($id);
        $form = $this->createForm(AnlageDcGroupsFormType::class, $anlage, [
            'anlagenId' => $id,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked())) {
            $successMessage = 'Plant data saved!';
            $em->persist($anlage);
            $em->flush();
            if ($form->get('saveclose')->isClicked()) {
                $this->addFlash('success', $successMessage);

                return $this->redirectToRoute('app_admin_anlagen_list');
            }
        }
        if ($form->isSubmitted() && $form->get('close')->isClicked()) {
            $this->addFlash('warning', 'Canceled. No data was saved.');

            return $this->redirectToRoute('app_admin_anlagen_list');
        }

        return $this->render('anlagen/edit_dcgroups.html.twig', [
            'anlageForm' => $form,
            'anlage' => $anlage,
        ]);
    }

    #[Route(path: '/admin/anlagen/editacgroups/{id}', name: 'app_admin_anlagen_edit_acgroups')]
    public function editAcGroups($id, EntityManagerInterface $em, Request $request, AnlagenRepository $anlagenRepository): RedirectResponse|Response
    {
        $anlage = $anlagenRepository->find($id);
        $form = $this->createForm(AnlageAcGroupsFormType::class, $anlage, [
            'anlagenId' => $id,
        ]);
        $pNom =
            $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked())) {
            $successMessage = 'Plant data saved!';
            $em->persist($anlage);
            $em->flush();
            if ($form->get('saveclose')->isClicked()) {
                $this->addFlash('success', $successMessage);

                return $this->redirectToRoute('app_admin_anlagen_list');
            }
        }
        if ($form->isSubmitted() && $form->get('close')->isClicked()) {
            $this->addFlash('warning', 'Canceled. No data was saved.');

            return $this->redirectToRoute('app_admin_anlagen_list');
        }

        return $this->render('anlagen/edit_acgroups.html.twig', [
            'anlageForm' => $form,
            'anlage' => $anlage,
        ]);
    }

    #[Route(path: '/admin/anlagen/editsensors/{id}', name: 'app_admin_anlagen_edit_sensors')]
    public function editSensors($id, EntityManagerInterface $em, Request $request, AnlagenRepository $anlagenRepository): RedirectResponse|Response
    {
        $anlage = $anlagenRepository->find($id);
        $form = $this->createForm(AnlageSensorsFormType::class, $anlage, [
            'anlagenId' => $id,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked())) {
            $successMessage = 'Plant data saved!';
            $em->persist($anlage);
            $em->flush();
            if ($form->get('saveclose')->isClicked()) {
                $this->addFlash('success', $successMessage);

                return $this->redirectToRoute('app_admin_anlagen_list');
            }
        }
        if ($form->isSubmitted() && $form->get('close')->isClicked()) {
            $this->addFlash('warning', 'Canceled. No data was saved.');

            return $this->redirectToRoute('app_admin_anlagen_list');
        }

        return $this->render('anlagen/edit_sensors.html.twig', [
            'anlageForm' => $form,
            'anlage' => $anlage,
        ]);
    }

    #[Route(path: '/admin/anlagen/editppcs/{id}', name: 'app_admin_anlagen_edit_ppcs')]
    public function editPpcs($id, EntityManagerInterface $em, Request $request, AnlagenRepository $anlagenRepository): RedirectResponse|Response
    {
        $anlage = $anlagenRepository->find($id);
        $form = $this->createForm(AnlageppcsFormType::class, $anlage, [
            'anlagenId' => $id,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked())) {
            $successMessage = 'Plant data saved!';
            $em->persist($anlage);
            $em->flush();
            if ($form->get('saveclose')->isClicked()) {
                $this->addFlash('success', $successMessage);

                return $this->redirectToRoute('app_admin_anlagen_list');
            }
        }
        if ($form->isSubmitted() && $form->get('close')->isClicked()) {
            $this->addFlash('warning', 'Canceled. No data was saved.');

            return $this->redirectToRoute('app_admin_anlagen_list');
        }

        return $this->render('anlagen/edit_ppcs.html.twig', [
            'anlageForm' => $form,
            'anlage' => $anlage,
        ]);
    }

    #[Route(path: '/admin/anlagen/delete/{id}', name: 'app_admin_anlage_delete')]
    #[IsGranted('ROLE_DEV')]
    public function delete($id, EntityManagerInterface $em, AnlagenRepository $anlagenRepository, Security $security): RedirectResponse
    {
        if ($this->isGranted('ROLE_DEV')) {
            /** @var Anlage|null $anlage */
            $anlage = $anlagenRepository->find($id);
            $em->remove($anlage);
            $em->flush();
        }

        return $this->redirectToRoute('app_admin_anlagen_list');
    }

    /**
     * Erzeugt alle Datenbanken für die Anlage
     * Braucht aber Zugriff auf die Datenbank der Anlagen (nicht per Doctrin).
     */
    private function createDatabasesForPlant(Anlage $anlage): bool
    {
        $databaseAcIst = 'CREATE TABLE IF NOT EXISTS '.$anlage->getDbNameIst()." (
              `db_id` bigint(11) NOT NULL AUTO_INCREMENT,
              `anl_id` int(11) NOT NULL,
              `stamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
              `inv` int(11) NOT NULL,
              `group_dc` int(11) NOT NULL,
              `group_ac` int(11) NOT NULL,
              `unit` int(11) NOT NULL,
              `wr_num` int(11) NOT NULL,
              `wr_idc` varchar(20) DEFAULT NULL,
              `wr_pac` varchar(20) DEFAULT NULL,
              `p_ac_blind` varchar(20) DEFAULT NULL,
              `i_ac` varchar(20) DEFAULT NULL,
              `i_ac_p1` varchar(20) DEFAULT NULL,
              `i_ac_p2` varchar(20) DEFAULT NULL,
              `i_ac_p3` varchar(20) DEFAULT NULL,
              `u_ac` varchar(20) DEFAULT NULL,
              `u_ac_p1` varchar(20) DEFAULT NULL,
              `u_ac_p2` varchar(20) DEFAULT NULL,
              `u_ac_p3` varchar(20) DEFAULT NULL,
              `p_ac_apparent` varchar(20) DEFAULT NULL,
              `frequency` varchar(20) DEFAULT NULL,
              `wr_udc` varchar(20) DEFAULT NULL,
              `wr_pdc` varchar(20) DEFAULT NULL,
              `wr_temp` varchar(20) DEFAULT NULL,
              `wr_cos_phi_korrektur` varchar(20) DEFAULT NULL,
              `e_z_evu` varchar(20) DEFAULT NULL,
              `temp_corr` varchar(20) DEFAULT NULL,
              `theo_power` varchar(20) DEFAULT NULL,
              `temp_cell` VARCHAR(20) DEFAULT NULL,
              `temp_cell_multi_irr` VARCHAR(20) DEFAULT NULL,
              `wr_mpp_current` json NOT NULL,
              `wr_mpp_voltage` json NOT NULL,
              `irr_anlage` json NOT NULL,
              `temp_anlage` json NOT NULL,
              `temp_inverter` json NOT NULL,
              `wind_anlage` json NOT NULL,
              PRIMARY KEY (`db_id`),
              UNIQUE KEY `unique_ist_record` (`stamp`,`group_ac`,`unit`) USING BTREE,
              KEY `stamp` (`stamp`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

        $databaseDcIst = 'CREATE TABLE IF NOT EXISTS '.$anlage->getDbNameIstDc()." (
              `db_id` bigint(11) NOT NULL AUTO_INCREMENT,
              `anl_id` int(11) NOT NULL,
              `stamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
              `wr_group` int(11) NOT NULL,
              `group_ac` int(11) NOT NULL,
              `wr_num` int(11) NOT NULL,
              `wr_idc` varchar(20) DEFAULT NULL,
              `wr_udc` varchar(20) DEFAULT NULL,
              `wr_pdc` varchar(20) DEFAULT NULL,
              `wr_temp` varchar(20) DEFAULT NULL,
              `wr_mpp_current` json NOT NULL,
              `wr_mpp_voltage` json NOT NULL,
              PRIMARY KEY (`db_id`),
              UNIQUE KEY `unique_ist_record` (`stamp`,`wr_group`,`wr_num`) USING BTREE,
              KEY `stamp` (`stamp`),
              KEY `wr_group` (`wr_group`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";


        $databaseDcSoll = 'CREATE TABLE IF NOT EXISTS '.$anlage->getDbNameDcSoll()." (
                  `db_id` bigint(11) NOT NULL AUTO_INCREMENT,
                  `anl_id` int(11) NOT NULL,
                  `stamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
                  `wr` int(11) NOT NULL,
                  `wr_num` int(11) NOT NULL,
                  `group_dc` int(11) NOT NULL,
                  `group_ac` int(11) NOT NULL,
                  `ac_exp_power` varchar(20) NOT NULL,
                  `ac_exp_power_evu` varchar(20) NOT NULL,
                  `ac_exp_power_no_limit` varchar(20) NOT NULL,
                  `dc_exp_power` varchar(20) NOT NULL,
                  `dc_exp_current` varchar(20) NOT NULL,
                  `dc_exp_voltage` varchar(20) NOT NULL,
                  `soll_imppmo` varchar(20) NOT NULL,
                  `soll_imppwr` varchar(20) NOT NULL,
                  `soll_pdcmo` varchar(20) NOT NULL,
                  `soll_pdcwr` varchar(20) NOT NULL,
                  `ws_tmp` varchar(20) NOT NULL,
                  PRIMARY KEY (`db_id`),
                  UNIQUE KEY `stamp_inverter` (`stamp`,`wr`),
                  KEY `stamp` (`stamp`)
            ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;";

    $databaseMeters = 'CREATE TABLE IF NOT EXISTS '.$anlage->getDbNameMeters()." (
                  `db_id` bigint(11) NOT NULL AUTO_INCREMENT,
                  `anl_id` int(11) NOT NULL,
                  `stamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
                  `group` int(11) NOT NULL DEFAULT '1',                      
                  `prod_power` varchar(20) NOT NULL,
                  `unit` varchar(20) NOT NULL,                      
                  PRIMARY KEY (`db_id`),
                  UNIQUE KEY `stamp_inverter` (`stamp`),
                  KEY `stamp` (`stamp`)
            ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;";

    $databasePPC = "CREATE TABLE IF NOT EXISTS ".$anlage->getDbNamePPC()." (
                      `db_id` bigint(11) NOT NULL AUTO_INCREMENT,
                      `anl_id` bigint(11) NOT NULL,
                      `anl_intnr` varchar(50),
                      `stamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
                      `p_ac_inv` varchar(20) DEFAULT NULL,
                      `q_ac_inv` varchar(20) DEFAULT NULL,
                      `pf_set` int(3) DEFAULT NULL,
                      `p_set_gridop_rel` int(3) DEFAULT NULL,
                      `p_set_rel` int(3) DEFAULT NULL,
                      `p_set_rpc_rel` int(3) DEFAULT NULL,
                      `q_set_rel` int(3) DEFAULT NULL,
                      `p_set_ctrl_rel` int(3) DEFAULT NULL,
                      `p_set_ctrl_rel_mean` int(3) DEFAULT NULL,
                        PRIMARY KEY (`db_id`),
                        UNIQUE KEY `unique_stamp` (`stamp`),
                        KEY `stamp` (`stamp`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $databaseSensorData = "CREATE TABLE IF NOT EXISTS ".$anlage->getDbNameSensorsData()." (
                              `db_id` bigint(11) NOT NULL AUTO_INCREMENT,
                              `date` varchar(50) DEFAULT NULL,
                              `stamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
                              `id_sensor` int(3) DEFAULT NULL,
                              `value` float DEFAULT NULL,
                              `gmo` float DEFAULT NULL,
                              PRIMARY KEY (`db_id`) USING BTREE,
                              UNIQUE KEY `unique_stamp_sensor` (`stamp`,`id_sensor`) USING BTREE,
                              KEY `stamp` (`stamp`) USING BTREE
                            ) ENGINE=InnoDB AUTO_INCREMENT=43021 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=DYNAMIC;";

        $databaseSections = "CREATE TABLE IF NOT EXISTS `pvp_data`.`db__pv_section_".$anlage->getAnlIntnr()."BX107` (
                              `id` BIGINT(11) NOT NULL AUTO_INCREMENT,
                              `stamp` VARCHAR(45) NOT NULL DEFAULT '0000-00-00 00:00:00',
                              `section` VARCHAR(45) NOT NULL,
                              `ac_power` VARCHAR(20) NOT NULL,
                              `dc_power` VARCHAR(20) NOT NULL,
                              `grid_power` VARCHAR(20) NULL,
                              `theo_power` VARCHAR(20) NULL,
                              `theo_power_ft` VARCHAR(20) NULL,
                              `ft_cor_factor` VARCHAR(20) NULL,
                              `temp_module` VARCHAR(20) NULL,
                              `temp_module_nrel` VARCHAR(20) NULL,
                              PRIMARY KEY (`id`),
                              UNIQUE INDEX `stamp_section` (`stamp` ASC, `section` ASC));
                            ";

        $conn = $this->pdoService->getPdoPlant();
        $conn->exec($databaseAcIst);
        $conn->exec($databaseDcIst);
        // $conn->exec($databaseAcSoll);
        if ($anlage->getUseGridMeterDayData() == 1){
            $conn->exec($databaseMeters);
        }
        $conn->exec($databaseDcSoll);
        $conn->exec($databasePPC);
        $conn->exec($databaseSensorData);
        if (false) $conn->exec($databaseSections);
        $conn = null;

        return true;

    }
}
