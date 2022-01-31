<?php

namespace App\Form\AssetManagement;

use App\Form\Type\SwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssetManagementeReportFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Production',SwitchType::class)
            ->add('ProductionPos', ChoiceType::class,[
                'expanded' => false,
                'multiple' => true,
                'choices' => [
                    '1' => 1,
                    '2' => 2,
                    '3' => 3,
                ],
            ])
            ->add('Availability',SwitchType::class)
            ->add('AvailabilityPos', ChoiceType::class,[
                'expanded' => false,
                'multiple' => true,
                'choices' => [
                    '1' => 1,
                    '2' => 2,
                    '3' => 3,
                ],
            ])
            ->add('Economics',SwitchType::class)
            ->add('EconomicsPos', ChoiceType::class,[
                'expanded' => false,
                'multiple' => true,
                'choices' => [
                    '1' => 1,
                    '2' => 2,
                    '3' => 3,
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label'     => 'submit',
                'attr'      => ['class' => 'primary save'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
