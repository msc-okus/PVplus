<?php

namespace App\Form\Tools;

use App\Entity\Anlage;

use App\Form\Model\ToolsModel;

use App\Repository\AnlagenRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Knp\Bundle\PaginatorBundle\DependencyInjection\Configuration;
use Symfony\Component\Validator\Constraints\File;


class ImportExcelFormType extends AbstractType
{
    public function __construct(
        private AnlagenRepository $anlagenRepository,
        private Security          $security
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isDeveloper = $this->security->isGranted('ROLE_DEV');
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');

        if ($this->security->isGranted('ROLE_G4N')) {
            $anlagen = $this->anlagenRepository->findAllActiveAndAllowed();
        } else {
            $eigner = $this?->security->getUser()?->getEigners()[0];
            $anlagen = $this->anlagenRepository->findAllIDByEigner($eigner);
        }

        $anlagen_toShow = [];
        $i = 0;
        foreach ($anlagen as $anlage) {
            if($anlage->getPathToImportScript() != ''){
                $anlagen_toShow[$i] = $anlage;
                $i++;
            }
        }


        $builder
            ->add('anlage', EntityType::class, [
                'label' => 'Please select a Plant',
                'class' => Anlage::class,
                'choices' => $anlagen_toShow,
                'choice_label' => 'anlName',
            ])
            ->add('File', FileType::class, [
                'mapped' => false,
                'label' => 'Excel File',
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [

                        ],
                    ]),
                ],
            ])
            ->add('startDate', HiddenType::class, [

            ])
            ->add('endDate', HiddenType::class, [

            ])


            // #############################################
            // ###          STEUERELEMENTE              ####
            // #############################################

            ->add('calc', SubmitType::class, [
                'label' => 'Start calculation',
                'attr' => ['class' => 'primary save'],
            ])
            ->add('close', SubmitType::class, [
                'label' => 'Close (do nothing)',
                'attr' => ['class' => 'secondary close', 'formnovalidate' => 'formnovalidate'],
            ]);
    }

##
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ToolsModel::class,
        ]);
    }
}
