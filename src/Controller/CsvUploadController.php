<?php

namespace App\Controller;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use App\Entity\AnlageCase6;
use App\Entity\AnlageFile;
use App\Entity\Case6Array;
use App\Entity\Case6Draft;

use App\Form\Case6\Case6ArrayFormType;
use App\Form\FileUpload\FileUploadFormType;
use App\Form\Model\Case6fix;
use App\Form\Owner\OwnerFormType;
use App\Repository\AnlagenRepository;
use App\Repository\Case6DraftRepository;
use App\Service\FunctionsService;
use App\Service\UploaderHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CsvUploadController extends AbstractController
{
    /**
     * @Route("/csv/upload", name="csv_upload")
     */
    public function index(): Response
    {
        return $this->render('csv_upload/index.html.twig', [
            'controller_name' => 'CsvUploadController',
        ]);
    }

    /**
     *@Route("/csv/upload/delete/{id}", name="csv_upload_delete")
     */
    public function delete_case($id, EntityManagerInterface $em, Case6DraftRepository $draftRepo, AnlagenRepository $anlRepo):Response
    {
        $case6draft = $draftRepo->findById($id)[0];

        $anlage = $case6draft->getAnlage();
        $Route = $this->generateUrl('csv_upload_list',["anlId" => $anlage->getAnlId()], UrlGeneratorInterface::ABS_PATH);
        if ($case6draft) {
            $em->remove($case6draft);
            $em->flush();
        }
        return $this->redirect($Route);

    }
    /**
     * @Route("/csv/upload/list/{anlId}", name="csv_upload_list")
     */
    public function list($anlId,Case6DraftRepository $draftRepo, AnlagenRepository $anlRepo){
        $anlage = $anlRepo->findIdLike($anlId);
        $array = $draftRepo->findAllByAnlage($anlage[0]);

        return $this->render('csv_upload/list.html.twig', [
            'case6' => $array,
            'anlId' => $anlId
        ]);
    }
    /**
     * @Route("/csv/upload/saveandfix/{anlId}", name="csv_upload_saveandfix")
     */
    public function saveandfix($anlId,Case6DraftRepository $draftRepo, AnlagenRepository $anlRepo, EntityManagerInterface $em,Request $request){


        $anlage = $anlRepo->findIdLike($anlId)[0];
        $array = $draftRepo->findAllByAnlage($anlage);
        foreach($array as $case6d){
            if($case6d->check() == "") {
                $case6 = new AnlageCase6();
                $case6->setAnlage($anlage);
                $case6->setInverter($case6d->getInverter());
                $case6->setReason($case6d->getReason());
                $case6->setStampFrom($case6d->getStampFrom());
                $case6->setStampTo($case6d->getStampTo());
                $em->remove($case6d);
                $em->persist($case6);
            }
            else $arraye[]=$case6d;
            }
        $em->flush();
            if($arraye != []){//hacer arraye un array de case6 en vez de case6draft, para que encaje con la plantilla del form
                $casefix = new Case6Array();
                $casefix->setCase6s($arraye);
                $form = $this->createForm(Case6ArrayFormType::class, $casefix);
                $form->handleRequest($request);
                return $this->render('csv_upload/fixing.html.twig',[
                    'caseForm' => $form,
                    'case6' => $arraye
                ]);
            }

        $Route = $this->generateUrl('csv_upload_list',["anlId" => $anlage->getAnlId()], UrlGeneratorInterface::ABS_PATH);

        return $this->redirect($Route);
    }
    //there is need to hide this, so none can call it from the searchbar
        /**
        * @Route("/csv/upload/save/{anlId}", name="csv_upload_save")
        */
    public function save($anlId,Case6DraftRepository $draftRepo, AnlagenRepository $anlRepo, EntityManagerInterface $em){
        $anlage = $anlRepo->findIdLike($anlId)[0];
        $array = $draftRepo->findAllByAnlage($anlage);

        foreach($array as $case6d){
            if($case6d->check() == "") {
                $case6 = new AnlageCase6();
                $case6->setAnlage($anlage);
                $case6->setInverter($case6d->getInverter());
                $case6->setReason($case6d->getReason());
                $case6->setStampFrom($case6d->getStampFrom());
                $case6->setStampTo($case6d->getStampTo());
                $em->remove($case6d);
                $em->persist($case6);
            }
        }

        $em->flush();
        $Route = $this->generateUrl('csv_upload_list',["anlId" => $anlage->getAnlId()], UrlGeneratorInterface::ABS_PATH);

        return $this->redirect($Route);
    }

        /**
         * @Route("/csv/upload/load", name="csv_upload_load")
         */

    public function load(Request $request, EntityManagerInterface $em, UploaderHelper $uploaderHelper, AnlagenRepository $anlRepo):Response
    {
        $form = $this->createForm(FileUploadFormType::class);
        $anlage = null;
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid() && ($form->get('import')->isClicked()) ) {

            $uploadedFile = $form['File']->getData();

            if ($uploadedFile) {

                //Here we upload the file and read it
                $newFile = $uploaderHelper->uploadImage($uploadedFile, "1","csv");
                $finder = new Finder();
                $finder->in("/usr/www/users/pvpluy/dev.jm/PVplus-4.0/public/uploads/csv/1/")->name($newFile['newFilename']);

                foreach ($finder as $file) {//there will be only one file but we have to iterate like this

                    $contents = $file->getContents();


                    foreach (\symfony\component\string\u($contents)->split("\r\n") as $row) {
                        $row = (string)$row;
                        $fields[] = [];
                        if (!strpos($row, "id;anlage;from;to;inv;reason")) {
                            $fields = \symfony\component\string\u($row)->split(";");
                            $Plant = (string)$fields[1];
                            $anlage = $anlRepo->findIdLike($Plant)[0];
                            $from = (string)$fields[2];
                            $to = (string)$fields[3];
                            $inv = (string)$fields[4];
                            $reason = (string)$fields[5];
                            if ($anlage != null) {


                                $case6Draft = new Case6Draft();
                                $case6Draft->setAnlage($anlage);
                                $case6Draft->setStampFrom(preg_replace('/[\x00-\x1F\x7F]/u', '', $from));
                                $case6Draft->setStampTo(preg_replace('/[\x00-\x1F\x7F]/u', '', $to));
                                $case6Draft->setInverter($inv);
                                $case6Draft->setReason(preg_replace('/[\x00-\x1F\x7F]/u', '', $reason));
                                $case6Draft->setError($case6Draft->check());
                                $em->persist($case6Draft);
                            }
                        }
                    }
                    $em->flush();
                    $Route = $this->generateUrl('csv_upload_list',["anlId" => $anlage->getAnlId()], UrlGeneratorInterface::ABS_PATH);

                    return $this->redirect($Route);
                }
            }

        }
        return $this->render('csv_upload/index.html.twig', [
            'uploadForm' => $form->createView(),
            'case6' => null
        ]);
    }

}
