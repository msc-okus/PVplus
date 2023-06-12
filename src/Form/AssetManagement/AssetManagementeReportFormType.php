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
            ->add('Production', SwitchType::class, [
                'required' => false,
                'data' => true,

            ])
            ->add('ProdCap', SwitchType::class, [
                'label' => 'Production & Capacity Factor',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ])
            ->add('CumulatForecastPVSYS', SwitchType::class, [
                'label' => 'Cumulative Forecast - PVSYST',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny']
            ])
            ->add('CumulatForecastG4N', SwitchType::class, [
                'label' => 'Cumulative Forecast - G4N',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ])
            ->add('CumulatLosses', SwitchType::class, [
                'label' => 'Cumulative Losses',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ])
            ->add('MonthlyProd', SwitchType::class, [
                'label' => 'Monthly Production',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ])
            ->add('PRTable', SwitchType::class, [
                'label' => 'PR Table',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ])
            ->add('DailyProd', SwitchType::class, [
                'label' => 'Daily Production',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ])
            ->add('Availability', SwitchType::class, [
                'required' => false,
                'data' => true,
            ])
            ->add('AvYearlyOverview', SwitchType::class, [
                'label' => 'Availability-Yearly Overview Heatmap Analysis',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny']
            ])
            ->add('AvMonthlyOverview', SwitchType::class, [
                'label' => 'Availability Analysis - Monthly Overview',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ])
            ->add('AvYearlyTicketOverview', SwitchType::class, [
                'label' => 'Availability Analysis - Yearly Overview',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ])
            ->add('AvInv', SwitchType::class, [
                'label' => 'Availability Inverter',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ])
            ->add('AnalysisHeatmap', SwitchType::class, [
                'label' => 'Analysis Heatmaps',
                'required' => false,
                'data' => false,
            ])
            ->add('StringCurr', SwitchType::class, [
                'label' => 'String Current Heatmap Analysis',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny']
            ])
            ->add('InvPow', SwitchType::class, [
                'label' => 'Inverter Power-DC Heatmap Analysis',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny']
            ])

            ->add('Economics', SwitchType::class, [
                'required' => false,
            ])

            ->add('submit', SubmitType::class, [
                'label' => 'submit',
                'attr' => [
                    'class' => 'primary save',
                    //'data-action' => 'click->modal-form#createReport',
                ],
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
