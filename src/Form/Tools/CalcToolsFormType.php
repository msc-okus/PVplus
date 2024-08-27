<?php

namespace App\Form\Tools;

use App\Entity\Anlage;
use App\Form\Model\ToolsModel;
use App\Repository\AnlagenRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CalcToolsFormType extends AbstractType
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

        if ($this->security->isGranted('ROLE_BETA')) {
            $anlagen = $this->anlagenRepository->findAllActiveAndAllowed();
        } else {
            $eigner = $this?->security->getUser()?->getEigners()[0];
            $anlagen = $this->anlagenRepository->findAllIDByEigner($eigner);
        }

        $choiceFunction = [
            'Update (recalculate) Plant Availability' => 'updatePA',
            'Display Calculated PA' => 'calcPA'
        ];


        $builder
            ->add('anlage', EntityType::class, [
                'label' => 'Please select a Plant',
                'class' => Anlage::class,
                'choices' => $anlagen,
                'choice_label' => 'anlName',
                'autocomplete' => true,
                'placeholder' => 'Please select a Plant',
                'tom_select_options' => [
                    'max-item' => 1,
                    'create' => false,
                ],
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


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ToolsModel::class,
            'required' => false,
        ]);
    }
}
