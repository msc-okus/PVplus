<?php

namespace App\Form\Tools;

use App\Entity\Anlage;
use App\Form\Model\ToolsModel;
use App\Repository\AnlagenRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class ToolsFormType extends AbstractType
{
    public function __construct(
        private AnlagenRepository $anlagenRepository,
        private Security $security
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isDeveloper = $this->security->isGranted('ROLE_DEV');
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');

        $choisesFuction = [
            'Expected' => 'expected',
            #'Update availability' => 'availability',
            'Update availability New' => 'availability-new',
            'Update PR' => 'pr',
        ];
        if ($isDeveloper) $choisesFuction['Generate Tickets (NOT Update)'] = 'generate-tickets';
        $choisesFuction2 = [
            'Load API Data' => 'load-api-data',
        ];

        $choisesPreselect = [
            'please Select ' => 'null',
            'Plant Data Tools' => 'dataload',
            'DataBase Tools' => 'dbtools',
        ];

        $builder
            ->add('anlage', EntityType::class, [
                'label' => 'please select a Plant',
                'class' => Anlage::class,
                'choices' => $this->anlagenRepository->findAllActiveAndAllowed(),
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
            ->add('function1', ChoiceType::class, [
                'choices' =>  $choisesFuction1,
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('function2', ChoiceType::class, [
                'choices' =>  $choisesFuction2,
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('preselect', ChoiceType::class, [
                'choices' => $choisesPreselect,
                'attr' => array('onchange' => 'changeFunction()'),
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
            ])

            ->addEventListener(FormEvents:: SUBMIT, function (FormEvent $event){
                    $attributes = [];
                    $form = $event->getForm();
                   dd($form);
                    $isDeveloper = $this->security->isGranted('ROLE_DEV');
#
                    if ($event->getData()['preselect'] != 'null') {
                        $attributes = ['' => ''];
                   }
                    if ($event->getData()['preselect'] === 'null') {
                        $attributes = ['disabled' => 'disabled'];
                    }
                    if ($event->getData()['preselect'] === 'dataload') {
                        $choisesFuction = [
                           'Expected (New)' => 'expected',
                            'Update availability' => 'availability',
                            'Update availability New' => 'availability-new',
                            'Update PR' => 'pr',
                        ];
                        if ($isDeveloper) $choisesFuction['Generate Tickets (NOT Update)'] = 'generate-tickets';
                    }
                   if ($event->getData()['preselect'] === 'dbtools') {
                        $choisesFuction = [
                            'Load API Data' => 'load-api-data',
                        ];
                    }
               #     dump($event->getData()['preselect']);
                    $form = $event->getForm();
                 #   // get the form element and its options
                     $config = $form->get('function')->getConfig();
                    # dd( $config->getType());
                     $options = $config->getOptions();
                     $data = $event->getData();
                    # dd( $options);
                  #  dump($attributes);
                    $form->add(
                        'function',
                        ChoiceType::class,
                        #array_replace(
                           #  $options, [
                        ['choices' => $choisesFuction, 'placeholder' => 'please Choose ...','attr' => $attributes]
                     #  ]
                    #)
                   );
                },
            );
        }
##
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ToolsModel::class,
        ]);
    }
}
