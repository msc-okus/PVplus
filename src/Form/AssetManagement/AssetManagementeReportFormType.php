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
                'data' => true,
            ])
            ->add('Production', SwitchType::class, [
                'label' => 'Performance Analysis',
                'data' => true,
            ])
            ->add('Availability', SwitchType::class, [
                'data' => true,
            ])
            ->add('AnalysisHeatmap', SwitchType::class, [
                'label' => 'Analysis Heatmaps',
                'data' => false,
            ])
            ->add('Economics', SwitchType::class, [
                'required' => false,
            ])
        ;

        if ($reportParts['ProductionCapFactor']) {
            $builder->add('ProdCap', SwitchType::class, [
                'label' => 'Energy Production & Capacity Factor',
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('ProdCap', SwitchType::class, [
                'label' => 'Energy Production & Capacity Factor (Unavailable)',
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }
        if($reportParts['PRPATable']) {
            $builder->add('PRPATable', SwitchType::class, [
                'label' => 'Technical PR and Availability',
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('PRPATable', SwitchType::class, [
                'label' => 'Technical PR and Availability (Unavailable)',
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
                ]);
        }
        if($reportParts['StringAssignment']) {
            $builder->add('StringAssignment', SwitchType::class, [
                'label' => 'String Assignment',
                'data' => true,
            ]);
        }
        else{
            $builder->add('StringAssignment', SwitchType::class, [
                'label' => 'String Assignment (Unavailable)',
                'data' => false,
                'attr' => [ 'read_only' => true],
                'disabled' => true,
            ]);
        }
        if($reportParts['MonthlyProd']) {
            $builder->add('MonthlyProd', SwitchType::class, [
                'label' => 'Monthly Production',
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('MonthlyProd', SwitchType::class, [
                'label' => 'Monthly Production (Unavailable)',
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }
        if ($reportParts['production_with_forecast']) {
            $builder->add('ProdWithForecast', SwitchType::class, [
                'label' => 'Production vs Forecast vs Expected',
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('ProdWithForecast', SwitchType::class, [
                'label' => 'Production vs Forecast vs Expected (Unavailable)',
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }
        if ($reportParts['CumForecastPVSYS']) {
            $builder->add('CumulatForecastPVSYS', SwitchType::class, [
                'label' => 'Cumulative Forecast',
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('CumulatForecastPVSYS', SwitchType::class, [
                'label' => 'Cumulative Forecast (Unavailable)',
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }
        if ($reportParts['CumForecastG4N']){
            $builder->add('CumulatForecastG4N', SwitchType::class, [
                'label' => 'Cumulative Forecast',
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('CumulatForecastG4N', SwitchType::class, [
                'label' => 'Cumulative Forecast (Unavailable)',
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }
        if ($reportParts['CumLosses']) {
            $builder->add('CumulatLosses', SwitchType::class, [
                'label' => 'Cumulative Losses',
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('CumulatLosses', SwitchType::class, [
                'label' => 'Cumulative Losses (Unavailable)',
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }
        if ($reportParts['PRTable']){
            $builder->add('PRTable', SwitchType::class, [
                'label' => 'PR Table',
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('PRTable', SwitchType::class, [
                'label' => 'PR Table (Unavailable)',
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,

            ]);
        }
        if ($reportParts['DailyProd']){
            $builder->add('DailyProd', SwitchType::class, [
                'label' => 'Daily Production',
                'data' => false,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('DailyProd', SwitchType::class, [
                'label' => 'Daily Production (Unavailable)',
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }

        if($reportParts['InverterRank']) {
            $builder->add('InvRank', SwitchType::class, [
                'label' => 'Inverter Ranking by PR',
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('InvRank', SwitchType::class, [
                'label' => 'Inverter Ranking by PR (Unavailable)',
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }

        if($reportParts['InverterEfficiencyRank']) {
            $builder->add('EfficiencyRank', SwitchType::class, [
                'label' => 'Efficiency Ranking',
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('EfficiencyRank', SwitchType::class, [
                'label' => 'Efficiency Ranking (Unavailable)',
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }
        if($reportParts['waterfallProd']) {
            $builder->add('waterfallProd', SwitchType::class, [
                'label' => 'Bucket Losses Diagram',
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('waterfallProd', SwitchType::class, [
                'label' => 'Waterfall Diagram (Unavailable)',
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }
        if($reportParts['maintenanceTicketTable']) {
            $builder->add('maintenanceTicketTable', SwitchType::class, [
                'label' => 'Maintenance Tickets Summary',
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('maintenanceTicketTable', SwitchType::class, [
                'label' => 'Maintenance Tickets Summary (Unavailable)',
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }
        if($reportParts['kpiTicketTable']) {
            $builder->add('kpiTicketTable', SwitchType::class, [
                'label' => 'kpi Tickets Summary',
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('kpiTicketTable', SwitchType::class, [
                'label' => 'kpi Tickets Summary (Unavailable)',
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }

        if($reportParts['InverterEfficiencyRank']) {
            $builder->add('AvYearlyTicketOverview', SwitchType::class, [
                'label' => ' Yearly overview',
                'data' => true,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('AvYearlyTicketOverview', SwitchType::class, [
                'label' => ' Yearly overview (Unavailable)',
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }
        if ($reportParts['AvailabilityMonth']){
            $builder->add('AvMonthlyOverview', SwitchType::class, [
                'label' => 'Monthly overview',
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
                'label' => 'Inverter current level heatmap',
                'data' => false,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('StringCurr', SwitchType::class, [
                'label' => 'Inverter current level heatmap (Unavailable)',
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }
        if($reportParts){

            $builder->add('InvPow', SwitchType::class, [
                'label' => 'Inverter power level heatmap',
                'data' => false,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('InvPow', SwitchType::class, [
                'label' => 'Inverter power level heatmap (Unavailable)',
                'required' => false,
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }
        if($reportParts['AvailabilityYearOverview']) {
            $builder->add('AvYearlyOverview', SwitchType::class, [
                'label' => 'Availability yearly heatmap',
                'data' => false,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('AvYearlyOverview', SwitchType::class, [
                'label' => 'Availability yearly heatmap (Unavailable)',
                'data' => false,
                'attr' => ['switch_size' => 'tiny', 'read_only' => true,],
                'disabled' => true,
            ]);
        }

        if($reportParts['AvailabilityByInverter']) {
            $builder->add('AvInv', SwitchType::class, [
                'label' => 'Availability inverter heatmap',
                'data' => false,
                'attr' => ['switch_size' => 'tiny']
            ]);
        }
        else{
            $builder->add('AvInv', SwitchType::class, [
                'label' => 'Availability inverter heatmap (Unavailable)',
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
            'param' => null,
            'required' => false,
        ]);
    }
}

