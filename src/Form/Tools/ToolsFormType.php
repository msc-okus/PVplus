<?php

namespace App\Form\Tools;

use App\Entity\Anlage;
use App\Form\Model\ToolsModel;
use App\Repository\AnlagenRepository;
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

class ToolsFormType extends AbstractType
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
            $anlagen = $this->anlagenRepository->findAllActiveAndAllowed();
        } else {
            $eigner = $this?->security->getUser()?->getEigners()[0];
            $anlagen = $this->anlagenRepository->findAllIDByEigner($eigner);
        }

        $choiceFunction = [
            'Plant Data Tools' => [
                'G4N Expected' => 'expected',
                'Update availability' => 'availability',
                'Update PR' => 'pr',
            ],
            'Database Tools' => [
                'Reload INAX Data' => 'api-load-inax-data',
                'Reload API Data' => 'api-load-data',
            ]
        ];

        if ($isDeveloper) $choiceFunction['Plant Data Tools']['Generate Tickets (NOT Update)'] = 'generate-tickets';

        $builder
            ->add('anlage', EntityType::class, [
                'label' => 'Please select a Plant',
                'class' => Anlage::class,
                'choices' => $anlagen,
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
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ToolsModel::class,
        ]);
    }
}
