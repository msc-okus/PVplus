<?php

namespace App\Form\AssetManagement;

use App\Form\Type\SwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssetManagementeReportFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Production',SwitchType::class,[
                'required'  => false
            ])
            ->add('ProdCap',SwitchType::class,[
                'label'    => 'Production & Capacity Factor',
                'required' => false,
            ])
            ->add('CumulatForecastPVSYS',SwitchType::class,[
                'label'     => 'Cumulative Forecast - PVSYST',
                'required'  => false
            ])
            ->add('CumulatForecastG4N',SwitchType::class,[
                'label'     => 'Cumulative Forecast - G4N',
                'required'  => false
            ])
            ->add('CumulatLosses',SwitchType::class,[
                'label'     => 'Cumulative Losses',
                'required'  => false
            ])
            ->add('MonthlyProd',SwitchType::class,[
                'label'     => 'Monthly Production',
                'required'  => false
            ])
            ->add('DailyProd',SwitchType::class,[
                'label'     => 'Daily Production',
                'required'  => false
            ])

            /*
            ->add('ProductionPos', ChoiceType::class,[
                'expanded' => false,
                'multiple' => false,
                'choices' => [
                    '1' => 1,
                    '2' => 2,
                    '3' => 3,
                ],
            ])
            */
            ->add('Availability',SwitchType::class,[
                'required' => false
            ])
            ->add('AvYearlyOverview',SwitchType::class,[
                'label'     => 'Availability-Yearly Overview',
                'required'  => false
            ])
            ->add('AvMonthlyOverview',SwitchType::class,[
                'label'     => 'Availability-Monthly Overview',
                'required'  => false
            ])
            ->add('AvInv',SwitchType::class,[
                'label'     => 'Availability Inverter',
                'required'  => false
            ])
            ->add('StringCurr',SwitchType::class,[
                'label'     => 'String Current',
                'required'  => false
            ])
            ->add('InvPow',SwitchType::class,[
                'label'     => 'Inverter Power-DC Heatmap',
                'required'  => false
            ])
            /*
            ->add('AvailabilityPos', ChoiceType::class,[
                'expanded' => false,
                'multiple' => false,
                'choices' => [
                    '1' => 1,
                    '2' => 2,
                    '3' => 3,
                ],
            ])
            */
            ->add('Economics',SwitchType::class, [
                'required' => false
            ])


            /*
            ->add('EconomicsPos', ChoiceType::class,[
                'expanded' => false,
                'multiple' => false,
                'choices' => [
                    '1' => 1,
                    '2' => 2,
                    '3' => 3,
                ],
            ])
            */
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
