<?php

namespace App\Form\ImportTools;

use App\Entity\Anlage;
use App\Form\Model\ImportToolsModel;
use App\Repository\AnlagenRepository;
use Knp\Bundle\PaginatorBundle\DependencyInjection\Configuration;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\ChoiceList\ChoiceList;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\SecurityBundle\Security;

class ImportToolsFormType extends AbstractType
{
    public function __construct(
        private readonly AnlagenRepository $anlagenRepository,
        private readonly Security          $security
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isDeveloper = $this->security->isGranted('ROLE_DEV');
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');

        if ($this->security->isGranted('ROLE_G4N')) {
            $anlagen = $this->anlagenRepository->findAllSymfonyImport();
        } else {
            $eigner = $this?->security->getUser()?->getEigners()[0];
            $anlagen = $this->anlagenRepository->findSymfonyImportByEigner($eigner);
        }

        $anlagen_toShow = [];
        $i = 0;
        foreach ($anlagen as $anlage) {
            $isSymfonyImport = null;
            $settings = $anlage->getSettings();
            if($settings){
                $isSymfonyImport = $settings->isSymfonyImport();
            }
            
            if($anlage->getPathToImportScript() != '' || $isSymfonyImport){

                $anlagen_toShow[$i] = $anlage;
                $i++;
            }
        }

        $choiceFunction = [
            'Import Tools' => [
                'Import API Data' => 'api-import-data',
            ]
        ];

        $choiceFunctionType = [
            'Import Type' => [
                'Import all' => 'api-import-all',
                'Import Weather only' => 'api-import-weather',
                'Import PPC only' => 'api-import-ppc',
                'Import PV-Ist only' => 'api-import-pvist',
            ]
        ];

        $builder
            ->add('anlage', EntityType::class, [
                'label' => 'Please select a Plant',
                'class' => Anlage::class,
                'choices' => $anlagen_toShow,
                'choice_label' => 'anlName',
            ])
            ->add('startDate', DateType::class, [
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'data' => new \DateTime('now'),
            ])
            ->add('endDate', DateType::class, [
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'data' => new \DateTime('now'),
            ])
            ->add('function', ChoiceType::class, [
                'choices' => $choiceFunction,
                'placeholder' => 'please Choose ...',
                'mapped' => false,
                'required' => true,
            ])
            ->add('importType', ChoiceType::class, [
                'choices' => $choiceFunctionType,
                'placeholder' => 'please Choose ...',
                'mapped' => false,
                'required' => true,
            ])

            // #############################################
            // ###          STEUERELEMENTE              ####
            // #############################################


            ;
    }

##
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ImportToolsModel::class,
        ]);
    }
}
