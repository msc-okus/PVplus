<?php

namespace App\Controller;

use App\Entity\AnlageCase6;
use App\Entity\AnlageFile;
use App\Form\FileUpload\FileUploadFormType;
use App\Form\Owner\OwnerFormType;
use App\Repository\AnlagenRepository;
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
     * @Route("/csv/upload/load", name="csv_upload_load")
     */
    public function load(Request $request, EntityManagerInterface $em, UploaderHelper $uploaderHelper, AnlagenRepository $anlRepo, FunctionsService $fun):Response
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

                foreach ($finder as $file){//there will be only one file but we have to iterate like this

                $contents = $file->getContents();


                foreach(\symfony\component\string\u($contents)->split(";;;;;") as $row){
                    $row = (String)$row;
                    $fields[]=[];
                    if(!strpos($row, "id;anlage;from;to;inv;reason")) {

                        $fields = \symfony\component\string\u($row)->split(";");
                        $Plant = (String)$fields[1];
                        $anlage = $anlRepo->findIdLike($Plant)[0];
                        $from = (String)$fields[2];
                        $to = (String)$fields[3];
                        $inv = (String)$fields[4];
                        $reason = (string)$fields[5];
                        if($anlage != null) {
                            $invs = $fun->readInverters($inv, $anlage);
                            foreach ($invs as $inverter) {
                                $case6 = new AnlageCase6();
                                $case6->setAnlage($anlage);
                                $case6->setStampFrom(preg_replace('/[\x00-\x1F\x7F]/u', '', $from));
                                $case6->setStampTo(preg_replace('/[\x00-\x1F\x7F]/u', '', $to));
                                $case6->setInverter($inverter);
                                $case6->setReason(preg_replace('/[\x00-\x1F\x7F]/u', '', $reason));

                                $array[] = [$case6, $case6->check()];
                            }
                        }

                    }

                }
                    dump($array);
            }
            }

        }
        return $this->render('csv_upload/index.html.twig', [
            'controller_name' => 'CsvUploadController',
            'uploadForm' => $form->createView(),
        ]);
    }
}
