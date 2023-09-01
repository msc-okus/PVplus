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
        $report = $options['param'] ?? null;
        $reportParts = $report->getPdfParts();

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
            ->add('Availability', SwitchType::class, [
                'required' => false,
                'data' => true,
            ])
            ->add('AnalysisHeatmap', SwitchType::class, [
                'label' => 'Analysis Heatmaps',
                'required' => false,
                'data' => false,
            ])
            ->add('Economics', SwitchType::class, [
                'required' => false,
            ])
        ;

        if ($reportParts['ProductionCapFactor']) {
            $builder->add('ProdCap', SwitchType::class, [
                'label' => 'Energy Production & Capacity Factor',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('ProdCap', SwitchType::class, [
                'label' => 'Energy Production & Capacity Factor (Unavailable)',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }
        if($reportParts['PRPATable']) {
            $builder->add('PRPATable', SwitchType::class, [
                'label' => 'Technical PR and Availability',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('PRPATable', SwitchType::class, [
                'label' => 'Technical PR and Availability (Unavailable)',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
                ]);
        }
        if($reportParts['MonthlyProd']) {
            $builder->add('MonthlyProd', SwitchType::class, [
                'label' => 'Monthly Production',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('MonthlyProd', SwitchType::class, [
                'label' => 'Monthly Production (Unavailable)',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }
        if ($reportParts['production_with_forecast']) {
            $builder->add('ProdWithForecast', SwitchType::class, [
                'label' => 'Production vs Forecast vs Expected',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('ProdWithForecast', SwitchType::class, [
                'label' => 'Production vs Forecast vs Expected (Unavailable)',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }
        if ($reportParts['CumForecastPVSYS']) {
            $builder->add('CumulatForecastPVSYS', SwitchType::class, [
                'label' => 'Cumulative Forecast',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('CumulatForecastPVSYS', SwitchType::class, [
                'label' => 'Cumulative Forecast (Unavailable)',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }
        if ($reportParts['CumForecastG4N']){
            $builder->add('CumulatForecastG4N', SwitchType::class, [
                'label' => 'Cumulative Forecast',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('CumulatForecastG4N', SwitchType::class, [
                'label' => 'Cumulative Forecast (Unavailable)',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }
        if ($reportParts['CumLosses']) {
            $builder->add('CumulatLosses', SwitchType::class, [
                'label' => 'Cumulative Losses',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('CumulatLosses', SwitchType::class, [
                'label' => 'Cumulative Losses (Unavailable)',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }
        if ($reportParts['PRTable']){
            $builder->add('PRTable', SwitchType::class, [
                'label' => 'PR Table',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('PRTable', SwitchType::class, [
                'label' => 'PR Table (Unavailable)',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,

            ]);
        }
        if ($reportParts['DailyProd']){
            $builder->add('DailyProd', SwitchType::class, [
                'label' => 'Daily Production',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('DailyProd', SwitchType::class, [
                'label' => 'Daily Production (Unavailable)',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }

        if($reportParts['InverterRank']) {
            $builder->add('InvRank', SwitchType::class, [
                'label' => 'Inverter Ranking by PR',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('InvRank', SwitchType::class, [
                'label' => 'Inverter Ranking by PR (Unavailable)',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }

        if($reportParts['InverterEfficiencyRank']) {
            $builder->add('EfficiencyRank', SwitchType::class, [
                'label' => 'Efficiency Ranking',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('EfficiencyRank', SwitchType::class, [
                'label' => 'Efficiency Ranking (Unavailable)',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }
        if($reportParts['waterfallProd']) {
            $builder->add('waterfallProd', SwitchType::class, [
                'label' => 'Waterfall Diagram',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('waterfallProd', SwitchType::class, [
                'label' => 'Waterfall Diagram (Unavailable)',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }
        if($reportParts['InverterEfficiencyRank']) {
            $builder->add('AvYearlyTicketOverview', SwitchType::class, [
                'label' => ' Yearly overview',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('AvYearlyTicketOverview', SwitchType::class, [
                'label' => ' Yearly overview (Unavailable)',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }
        if ($reportParts['AvailabilityMonth']){
            $builder->add('AvMonthlyOverview', SwitchType::class, [
                'label' => 'Monthly overview',
                'required' => false,
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('AvMonthlyOverview', SwitchType::class, [
                'label' => 'Monthly overview (Unavailable)',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }
        if($reportParts['String']){
            $builder->add('StringCurr', SwitchType::class, [
                'label' => 'String level heatmap',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('StringCurr', SwitchType::class, [
                'label' => 'String level heatmap (Unavailable)',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }
        if($reportParts){

            $builder->add('InvPow', SwitchType::class, [
                'label' => 'Inverter level heatmap',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('InvPow', SwitchType::class, [
                'label' => 'Inverter level heatmap (Unavailable)',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }
        if($reportParts['AvailabilityYearOverview']) {
            $builder->add('AvYearlyOverview', SwitchType::class, [
                'label' => 'Availability yearly heatmap',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('AvYearlyOverview', SwitchType::class, [
                'label' => 'Availability yearly heatmap (Unavailable)',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }

        if($reportParts['AvailabilityByInverter']) {
            $builder->add('AvInv', SwitchType::class, [
                'label' => 'Availability inverter heatmap',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('AvInv', SwitchType::class, [
                'label' => 'Availability inverter heatmap (Unavailable)',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }




            $builder->add('submit', SubmitType::class, [
                'label' => 'submit',
                'attr' => [
                    'class' => 'primary save',
                    //'data-action' => 'click->modal-form#createReport',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
            'param' => null
        ]);
    }
}

