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
            ->add('TechnicalPV', SwitchType::class, [
                'label' => 'Technical PV Performance',
                'required' => false,
                'data' => true,
            ])
            ->add('Production', SwitchType::class, [
                'label' => 'Performance Analysis',
                'required' => false,
                'data' => true,
            ])
            ->add('ProdCap', SwitchType::class, [
                'label' => 'Energy Production & Capacity Factor',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ])
            ->add('ProdWithForecast', SwitchType::class, [
                'label' => 'Production vs Forecast vs Expected',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ])
            ->add('InvRank', SwitchType::class, [
                'label' => 'Inverter Ranking by PR',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ])
            ->add('PRPATable', SwitchType::class, [
                'label' => 'Technical PR and Availability',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ])
            ->add('CumulatForecastPVSYS', SwitchType::class, [
                'label' => 'Cumulative Forecast',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ])
            ->add('CumulatForecastG4N', SwitchType::class, [
                'label' => 'Cumulative Forecast',
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
            ->add('EfficiencyRank', SwitchType::class, [
                'label' => 'Efficiency Ranking',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ])
            ->add('DailyProd', SwitchType::class, [
                'label' => 'Daily Production',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny']
            ])
            ->add('Availability', SwitchType::class, [
                'label' => 'Ticket & Availability',
                'required' => false,
                'data' => true,
            ])
            ->add('AvYearlyOverview', SwitchType::class, [
                'label' => 'Availability yearly heatmap',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny']
            ])
            ->add('AvMonthlyOverview', SwitchType::class, [
                'label' => 'Monthly overview',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ])
            ->add('AvYearlyTicketOverview', SwitchType::class, [
                'label' => ' Yearly overview',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ])
            ->add('AvInv', SwitchType::class, [
                'label' => 'Availability inverter heatmap',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny']
            ])
            ->add('AnalysisHeatmap', SwitchType::class, [
                'label' => 'Analysis Heatmaps',
                'required' => false,
                'data' => false,
            ])
            ->add('StringCurr', SwitchType::class, [
                'label' => 'String level heatmap',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny']
            ])
            ->add('InvPow', SwitchType::class, [
                'label' => 'Inverter level heatmap',
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
