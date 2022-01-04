<?php

namespace App\Controller;

use App\Entity\AnlageCase6;
use App\Entity\AnlageFile;
use App\Entity\Case6Draft;
use App\Form\FileUpload\FileUploadFormType;
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
     *@Route("/csv/upload/delete", name="csv_upload_delete")
     */
    public function delete_case($array, $id):Response
    {
        dd($array);
        return $this->render('csv_upload/index.html.twig', [
            'uploadForm' => $form->createView(),
            'case6' => $array
        ]);
    }
    /**
     * @Route("/csv/upload/list/{anlId}", name="csv_upload_list")
     */
    public function list($anlId,Case6DraftRepository $draftRepo, AnlagenRepository $anlRepo){

        $anlage = $anlRepo->findIdLike($anlId);

        $array = $draftRepo->findAllByAnlage($anlage);
        dd($array);
        return $this->render('csv_upload/list.html.twig', [
            'case6' => $array
        ]);
    }
    /**
     * @Route("/csv/upload/load", name="csv_upload_load")
     */

    public function load(Request $request, EntityManagerInterface $em, UploaderHelper $uploaderHelper, AnlagenRepository $anlRepo,  FunctionsService $fun):Response
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
                    //dd($draftRepo->findAllByAnlage($anlage));

                }
            }

        }
        return $this->render('csv_upload/index.html.twig', [
            'uploadForm' => $form->createView(),
            'case6' => null
        ]);
    }
}
