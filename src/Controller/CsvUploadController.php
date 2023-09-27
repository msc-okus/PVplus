<?php

namespace App\Controller;

use App\Entity\AnlageCase6;
use App\Entity\Case6Array;
use App\Entity\Case6Draft;
use App\Form\Case6\Case6ArrayFormType;
use App\Form\FileUpload\FileUploadFormType;
use App\Repository\AnlagenRepository;
use App\Repository\Case6DraftRepository;
use App\Service\UploaderHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use function Symfony\Component\String\u;

class CsvUploadController extends AbstractController
{
    #[Route(path: '/csv/upload/delete/{id}', name: 'csv_upload_delete')]
    public function deleteCase($id, EntityManagerInterface $em, Case6DraftRepository $draftRepo, AnlagenRepository $anlRepo): Response
    {
        $case6draft = $draftRepo->findById($id)[0];
        $anlage = $case6draft->getAnlage();
        $Route = $this->generateUrl('csv_upload_list', ['anlId' => $anlage->getAnlId()], UrlGeneratorInterface::ABSOLUTE_PATH);
        if ($case6draft) {
            $em->remove($case6draft);
            $em->flush();
        }

        return $this->redirect($Route);
    }

    #[Route(path: '/csv/upload/list/{anlId}', name: 'csv_upload_list')]
    public function list($anlId, Case6DraftRepository $draftRepo, AnlagenRepository $anlRepo): Response
    {
        $anlage = $anlRepo->find($anlId);
        $array = $draftRepo->findAllByAnlage($anlage);

        return $this->render('csv_upload/list.html.twig', [
            'anlage' => $anlage,
            'case6' => $array,
            'anlId' => $anlId,
        ]);
    }

    #[Route(path: '/csv/upload/saveandfix/{anlId}', name: 'csv_upload_saveandfix')]
    public function saveAndFix($anlId, Case6DraftRepository $draftRepo, AnlagenRepository $anlRepo, EntityManagerInterface $em, Request $request): Response
    {
        $anlage = $anlRepo->findIdLike($anlId)[0];
        $array = $draftRepo->findAllByAnlage($anlage);
        $array[0]->check();
        foreach ($array as $case6draft) {// we iterate over all the cases of this plant and see which we can save and which we must fix
            $case6 = new AnlageCase6();
            $case6->setAnlage($anlage);
            $case6->setInverter($case6draft->getInverter());
            $case6->setReason($case6draft->getReason());
            $case6->setStampFrom($case6draft->getStampFrom());
            $case6->setStampTo($case6draft->getStampTo());
            if ($case6->check() == '') {
                $em->remove($case6draft);
                $em->persist($case6);
            } else {
                $arraydraft[] = $case6draft; // this array contains all the drafts that were not submited beacause they have errors
                $arraycase[] = $case6; // it contains exactly the same as the array above but case6 instead of case6drafts
                // we make this because we need case6's to call the form
            }
        }
        $em->flush();
        if ($arraydraft != []) {
            $casefix = new Case6Array();
            $casefix->setCase6s($arraycase);
            $form = $this->createForm(Case6ArrayFormType::class, $casefix); // we make the form with the array of case6's as Case6Array class
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->get('save')->isClicked()) { // if the form has been submitted we proceed to do more or less the same as above*
                $index = 0;
                foreach ($form->getData()->getCase6s() as $case6) {
                    $case6draft = $arraydraft[$index]; // *thats why I say more or less: we need the array from above to get the id of the draft to remove it
                    $em->remove($case6draft);
                    if ($case6->check() == '') {
                        $em->persist($case6); // if there is no errors we persist the case6
                    } else {// if there is errors we create a new draft
                        $newdraft = new Case6Draft();
                        $newdraft->setInverter($case6->getInverter());
                        $newdraft->setAnlage($anlage);
                        $newdraft->setStampFrom($case6->getStampFrom());
                        $newdraft->setStampTo($case6->getStampTo());
                        $newdraft->setReason($case6->getReason());
                        $newdraft->setError($case6->check());
                        $em->persist($newdraft);
                        // $newArrayDraft[]=$newdraft;
                    }
                    ++$index;
                }
                $em->flush();
            } else {
                return $this->render('csv_upload/fixing.html.twig', [
                'caseForm' => $form,
                'case6' => $arraydraft,
            ]);
            }
        }
        $Route = $this->generateUrl('csv_upload_list', ['anlId' => $anlId], UrlGeneratorInterface::ABS_PATH);

        return $this->redirect($Route);
    }

    #[Route(path: '/csv/upload/save/{anlId}', name: 'csv_upload_save')]
    public function save($anlId, Case6DraftRepository $draftRepo, AnlagenRepository $anlRepo, EntityManagerInterface $em): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $anlage = $anlRepo->findIdLike($anlId)[0];
        $array = $draftRepo->findAllByAnlage($anlage);
        foreach ($array as $case6d) {
            if ($case6d->check() == '') {
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
        $Route = $this->generateUrl('csv_upload_list', ['anlId' => $anlage->getAnlId()], UrlGeneratorInterface::ABS_PATH);

        return $this->redirect($Route);
    }

    #[Route(path: '/csv/upload/load', name: 'csv_upload_load')]
    public function load(Request $request, EntityManagerInterface $em, UploaderHelper $uploaderHelper, AnlagenRepository $anlRepo, $uploadsPath): Response
    {
        $form = $this->createForm(FileUploadFormType::class);
        $anlage = null;
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $form->get('import')->isClicked()) {
            $uploadedFile = $form['File']->getData();

            if ($uploadedFile) {
                // Here we upload the file and read it
                $newFile = $uploaderHelper->uploadImage($uploadedFile, '1', 'csv');
                $finder = new Finder();
                $finder->in($uploadsPath.'/csv/1/')->name($newFile['newFilename']);

                foreach ($finder as $file) {// there will be only one file but we have to iterate like this
                    $contents = $file->getContents();

                    foreach (u($contents)->split("\r\n") as $row) {
                        $row = (string) $row;
                        $fields[] = [];
                        if (!strpos($row, 'id;anlage;from;to;inv;reason') && strcmp($row, ";;;;;\r") != 0) {
                            $fields = u($row)->split(';');
                            $Plant = (string) $fields[1];
                            $anlage = $anlRepo->findIdLike($Plant)[0];
                            $from = (string) $fields[2];
                            $to = (string) $fields[3];
                            $inv = (string) $fields[4];
                            $reason = (string) $fields[5];
                            if ($anlage != null) {
                                $anlId = $anlage->getAnlId();
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
                    $Route = $this->generateUrl('csv_upload_list', ['anlId' => $anlId], UrlGeneratorInterface::ABS_PATH);

                    return $this->redirect($Route);
                }
            }
        }

        return $this->render('csv_upload/index.html.twig', [
            'uploadForm' => $form,
            'case6' => null,
        ]);
    }
}
