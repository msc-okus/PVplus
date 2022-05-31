<?php

namespace App\Controller;

use App\Entity\Anlage;
use App\Entity\AnlageFile;
use App\Entity\EconomicVarNames;
use App\Form\Anlage\AnlageAcGroupsFormType;
use App\Form\Anlage\AnlageConfigFormType;
use App\Form\Anlage\AnlageDcGroupsFormType;
use App\Form\Anlage\AnlageFormType;
use App\Form\Anlage\AnlageCustomerFormType;
use App\Form\Anlage\AnlageNewFormType;
use App\Helper\G4NTrait;
use App\Repository\AnlageFileRepository;
use App\Repository\AnlagenRepository;
use App\Repository\EconomicVarNamesRepository;
use App\Repository\EconomicVarValuesRepository;
use App\Service\UploaderHelper;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class AnlagenAdminController extends BaseController
{
    use G4NTrait;
    #[Route(path: '/admin/anlagen/new', name: 'app_admin_anlagen_new')]
    public function new(EntityManagerInterface $em, Request $request) : RedirectResponse|Response
    {
        $form = $this->createForm(AnlageNewFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked())) {
            /** @var Anlage $anlage */
            $anlage = $form->getData();
            $em->persist($anlage);
            $em->flush();
            $anlage->setAnlIntnr('CX' . $anlage->getAnlagenId());
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
            'anlageForm'   => $form->createView(),
        ]);
    }

    #[Route(path: '/admin/anlagen/list', name: 'app_admin_anlagen_list')]
    public function list(Request $request, PaginatorInterface $paginator, AnlagenRepository $anlagenRepository) : Response
    {
        $q = $request->query->get('qp');
        if ($request->query->get('search') == 'yes' && $q == '') $request->getSession()->set('qp', '');
        if ($q) $request->getSession()->set('qp', $q);
        if ($q == "" && $request->getSession()->get('qp') != "") {
            $q = $request->getSession()->get('qp');
            $request->query->set('qp', $q);
        }
        $queryBuilder = $anlagenRepository->getWithSearchQueryBuilder($q);
        $pagination = $paginator->paginate(
            $queryBuilder, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            25                                         /*limit per page*/
        );
        return $this->render('anlagen/list.html.twig', [
            'pagination' => $pagination,
        ]);
    }


    #[Route(path: '/admin/anlagen/edit/{id}', name: 'app_admin_anlagen_edit')]
    public function edit($id, EntityManagerInterface $em, Request $request, AnlagenRepository $anlagenRepository) : RedirectResponse|Response
    {
        $anlage = $anlagenRepository->find($id);
        $form = $this->createForm(AnlageFormType::class, $anlage, [
            'anlagenId' => $id,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked() || $form->get('savecreatedb')->isClicked() ) ) {

            $successMessage = 'Plant data saved!';
            if ($form->get('savecreatedb')->isClicked()) {
                if ($this->createDatabasesForPlant($anlage)) $successMessage = 'Plant data saved and DB created.';
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
            'anlageForm'    => $form->createView(),
            'anlage'        => $anlage,
        ]);
    }

    /**
     * @param $id
     * @param EntityManagerInterface $em
     * @param Request $request
     * @param AnlagenRepository $anlagenRepository
     * @param EconomicVarNamesRepository $ecoNamesRepo
     * @return RedirectResponse|Response
     */
    #[Route(path: '/admin/anlagen/editconfig/{id}', name: 'app_admin_anlagen_edit_config')]
    public function editConfig($id, EntityManagerInterface $em, Request $request, AnlagenRepository $anlagenRepository, EconomicVarNamesRepository $ecoNamesRepo, UploaderHelper $uploaderHelper, AnlageFileRepository $RepositoryUpload) : RedirectResponse|Response
    {
        $upload = new AnlageFile();
        $anlage = $anlagenRepository->find($id);
        $imageuploaded = $RepositoryUpload->findOneBy(['path' => $anlage->getPicture()]);
        if($imageuploaded != null) {
            $isupload = 'yes';
        }
        else $isupload = 'no';
        $economicVarNames1 =new EconomicVarNames();
        if($ecoNamesRepo->findByAnlage($id)[0] != null) {
            $economicVarNames1 = $ecoNamesRepo->findByAnlage($id)[0];// will be used to load and display the already defined names
        }
        $form = $this->createForm(AnlageConfigFormType::class, $anlage, [
            'anlagenId' => $id,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked() ) ) {
            $uploadedFile = $form['picture']->getData();
            if ($uploadedFile) {
                $isupload = "yes";
                $newFile = $uploaderHelper->uploadImage($uploadedFile, $id, "owner");
                $newFilename = $newFile['newFilename'];
                $mimeType = $newFile['mimeType'];
                $uploadsPath ='uploads/'.UploaderHelper::EIGNER_LOGO.'/'.$id.'/'.$newFilename;
                $upload->setFilename($newFilename)
                    ->setMimeType($mimeType)
                    ->setPath($uploadsPath)
                    ->setPlant(null)
                    ->setStamp(date('Y-m-d H:i:s'));

                $em->persist($upload);
                $em->flush();

                $anlage->setPicture($uploadsPath);
            }
            if($economicVarNames1==null) {
                $economicVarNames = new EconomicVarNames();
                $economicVarNames->setparams($anlage, $form->get('var_1')->getData(), $form->get('var_2')->getData(), $form->get('var_3')->getData(), $form->get('var_4')->getData(), $form->get('var_5')->getData(), $form->get('var_6')->getData()
                    , $form->get('var_7')->getData(), $form->get('var_8')->getData(), $form->get('var_9')->getData(), $form->get('var_10')->getData(), $form->get('var_11')->getData(), $form->get('var_12')->getData(), $form->get('var_13')->getData(), $form->get('var_14')->getData(), $form->get('var_15')->getData());
            }
            else{
                $economicVarNames = $economicVarNames1;
                $economicVarNames->setparams($anlage, $form->get('var_1')->getData(), $form->get('var_2')->getData(), $form->get('var_3')->getData(), $form->get('var_4')->getData(), $form->get('var_5')->getData(), $form->get('var_6')->getData()
                    , $form->get('var_7')->getData(), $form->get('var_8')->getData(), $form->get('var_9')->getData(), $form->get('var_10')->getData(), $form->get('var_11')->getData(), $form->get('var_12')->getData(), $form->get('var_13')->getData(), $form->get('var_14')->getData(), $form->get('var_15')->getData());

            }

            //TODO: think and work on the switches, they are quite complex!
            $anlage->setEconomicVarNames($economicVarNames);
            $successMessage = 'Plant data saved!';
            $em->persist($anlage);
            $em->flush();
            $imageuploaded = $RepositoryUpload->findOneBy(['path' => $anlage->getPicture()]);
            if($form->get('save')->isClicked()){
                if ($imageuploaded != null) {
                    return $this->render('anlagen/editconfig.html.twig', [
                        'anlageForm' => $form->createView(),
                        'anlage' => $anlage,
                        'econames' => $economicVarNames1,
                        'isupload' => $isupload,
                        'imageuploadet' => $imageuploaded->getPath()
                    ]);
                }
                else {
                    return $this->render('anlagen/editconfig.html.twig', [
                        'anlageForm' => $form->createView(),
                        'anlage' => $anlage,
                        'econames' => $economicVarNames1,
                        'isupload' => $isupload
                    ]);
                }
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
        if ($imageuploaded != null) {
            return $this->render('anlagen/editconfig.html.twig', [
                'anlageForm' => $form->createView(),
                'anlage' => $anlage,
                'econames' => $economicVarNames1,
                'isupload' => $isupload,
                'imageuploadet' => $imageuploaded->getPath()
            ]);
        }
        else {
            return $this->render('anlagen/editconfig.html.twig', [
                'anlageForm' => $form->createView(),
                'anlage' => $anlage,
                'econames' => $economicVarNames1,
                'isupload' => $isupload
            ]);
        }
    }

    #[Route(path: '/admin/anlagen/editdcgroups/{id}', name: 'app_admin_anlagen_edit_dcgroups')]
    public function editDcGroups($id, EntityManagerInterface $em, Request $request, AnlagenRepository $anlagenRepository) : RedirectResponse|Response
    {
        $anlage = $anlagenRepository->find($id);
        $form = $this->createForm(AnlageDcGroupsFormType::class, $anlage, [
            'anlagenId' => $id,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked() ) ) {

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
            'anlageForm'    => $form->createView(),
            'anlage'        => $anlage,
        ]);
    }

    #[Route(path: '/admin/anlagen/editacgroups/{id}', name: 'app_admin_anlagen_edit_acgroups')]
    public function editAcGroups($id, EntityManagerInterface $em, Request $request, AnlagenRepository $anlagenRepository) : RedirectResponse|Response
    {
        $anlage = $anlagenRepository->find($id);
        $form = $this->createForm(AnlageAcGroupsFormType::class, $anlage, [
            'anlagenId' => $id,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked() ) ) {

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
            'anlageForm'    => $form->createView(),
            'anlage'        => $anlage,
        ]);
    }

    /**
     * @IsGranted("ROLE_DEV")
     */
    #[Route(path: '/admin/anlagen/delete/{id}', name: 'app_admin_anlage_delete')]
    public function delete($id, EntityManagerInterface $em, Request $request, AnlagenRepository $anlagenRepository, Security $security) : RedirectResponse
    {
        if ($this->isGranted('ROLE_DEV'))
        {
            /** @var Anlage|null $anlage */
            $anlage = $anlagenRepository->find($id);
            $em->remove($anlage);
            $em->flush();
        }
        return $this->redirectToRoute('app_anlagen_list');
    }

    /**
     * Erzeugt alle Datenbanken für die Anlage
     * Braucht aber Zugriff auf die Datenbank der Anlagen (nicht per Doctrin)
     * @param Anlage $anlage
     * @return bool
     */
    private function createDatabasesForPlant(Anlage $anlage): bool
    {
        if ($anlage) {
            $databaseAcIst = "CREATE TABLE IF NOT EXISTS " . $anlage->getDbNameIst() . " (
                  `db_id` bigint(11) NOT NULL AUTO_INCREMENT,
                  `anl_id` int(11) NOT NULL,
                  `stamp` timestamp NOT NULL,
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

            $databaseDcIst = "CREATE TABLE IF NOT EXISTS " . $anlage->getDbNameIstDc() . " (
                  `db_id` bigint(11) NOT NULL AUTO_INCREMENT,
                  `anl_id` int(11) NOT NULL,
                  `stamp` timestamp NOT NULL,
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

            /*
            $databaseAcSoll = "CREATE TABLE IF NOT EXISTS " . $anlage->getDbNameAcSoll() . " (
                    `db_id` bigint(11) NOT NULL AUTO_INCREMENT,
                    `anl_id` int(11) NOT NULL,
                    `anl_intnr` varchar(20) NOT NULL,
                    `stamp` timestamp NOT NULL,
                    `grp_id` int(11) NOT NULL,
                    `exp_kwh` varchar(20) NOT NULL,
                    PRIMARY KEY (`db_id`), 
                    KEY `stamp` (`stamp`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
            */

            $databaseDcSoll = "CREATE TABLE IF NOT EXISTS " . $anlage->getDbNameDcSoll() . " (
                      `db_id` bigint(11) NOT NULL AUTO_INCREMENT,
                      `anl_id` int(11) NOT NULL,
                      `stamp` timestamp NOT NULL,
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


            $conn = self::getPdoConnection();
            $conn->exec($databaseAcIst);
            $conn->exec($databaseDcIst);
            //$conn->exec($databaseAcSoll);
            $conn->exec($databaseDcSoll);
            $conn = null;

            return true;
        } else {
            return false;
        }
    }
}
