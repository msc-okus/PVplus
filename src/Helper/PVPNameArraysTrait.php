<?php

namespace App\Helper;

use Symfony\Contracts\Translation\TranslatorInterface;

trait PVPNameArraysTrait
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public static function prFormulars(): array
    {
        return [
            'No Cust PR' => 'no',
            'Groningen' => 'Groningen',
            'EnergyProduced / (Theo.Energy + PA)' => 'Veendam',
            'EnergyProduced / (Theo.Energy * Ft)' => 'Lelystad',
            'Ladenburg' => 'Ladenburg',
        ];;
    }

    public static function paFormulars(): array
    {
        return [
            'ti / (titheo - tiFM)'  => 1,
            'ti / titheo'           => 2,
        ];
    }

    public static function yearsArray(): array
    {
        return [
            2021 => 2021,
            2022 => 2022,
            2023 => 2023,
            2024 => 2024
        ];
    }

    public static function timeArray(): array
    {
        return [
            '+5' => '5',
            '+4' => '4',
            '+3.75' => '3.75',
            '+3.50' => '3.50',
            '+3.25' => '3.25',
            '+3' => '3',
            '+2.75' => '2.75',
            '+2.50' => '2.50',
            '+2.25' => '2.25',
            '+2' => '2',
            '+1.75' => '1.75',
            '+1.50' => '1.50',
            '+1.25' => '1.25',
            '+1' => '1',
            '+0.75' => '0.75',
            '+0.50' => '0.50',
            '+0.25' => '0.25',
            '0' => '0',
            '-0.25' => '-0.25',
            '-0.50' => '-0.50',
            '-0.75' => '-0.75',
            '-1' => '-1',
            '-1.25' => '-1.25',
            '-1.50' => '-1.50',
            '-1.75' => '-1.75',
            '-2' => '-2',
            '-2.25' => '-2.25',
            '-2.50' => '-2.50',
            '-2.75' => '-2.75',
            '-3' => '-3',
            '-3.25' => '-3.25',
            '-3.50' => '-3.50',
            '-3.75' => '-3.75',
            '-4' => '-4',
            '-5' => '-5',
        ];
    }

    public static function reportStati(): array
    {
        // Values for Report Status
        $reportStati[0]  = 'final';
        $reportStati[1]  = 'final FAC';
        $reportStati[3]  = 'under observation';
        $reportStati[5]  = 'proof reading';
        $reportStati[9]  = 'archive (g4n only)';
        $reportStati[10] = 'draft (g4n only)';
        $reportStati[11] = 'wrong (g4n only)';

        return $reportStati;
    }

    public function ticketStati(): array
    {
        $status[$this->translator->trans('ticket.status.10')] = 10; // New
        $status[$this->translator->trans('ticket.status.30')] = 30; // Work in Progress
        $status[$this->translator->trans('ticket.status.40')] = 40; // wait external
        $status[$this->translator->trans('ticket.status.90')] = 90; // Closed

        return $status;
    }

    public function ticketPriority(): array
    {
        $spriority[$this->translator->trans('ticket.priority.10')] = 10; //Low
        $spriority[$this->translator->trans('ticket.priority.20')] = 20; //Normal
        $spriority[$this->translator->trans('ticket.priority.30')] = 30; //High
        $spriority[$this->translator->trans('ticket.priority.40')] = 40; //Urgent


        return $spriority;
    }

    public function errorCategorie(): array
    {
        $errorCategory[$this->translator->trans('ticket.error.category.10')] = 10; //data gap
        $errorCategory[$this->translator->trans('ticket.error.category.20')] = 20; //inverter error
        $errorCategory[$this->translator->trans('ticket.error.category.30')] = 30; //grid error
        $errorCategory[$this->translator->trans('ticket.error.category.40')] = 40; //weather
        $errorCategory[$this->translator->trans('ticket.error.category.50')] = 50; //external control
        $errorCategory[$this->translator->trans('ticket.error.category.60')] = 60; //power/expected error

        $errorCategory[$this->translator->trans('ticket.error.category.7')][$this->translator->trans('ticket.error.category.70')] = 70; //performance ticket
        $errorCategory[$this->translator->trans('ticket.error.category.7')][$this->translator->trans('ticket.error.category.71')] = 71; //performance ticket
        $errorCategory[$this->translator->trans('ticket.error.category.7')][$this->translator->trans('ticket.error.category.72')] = 72; //performance ticket
        $errorCategory[$this->translator->trans('ticket.error.category.7')][$this->translator->trans('ticket.error.category.73')] = 73; //performance ticket
        $errorCategory[$this->translator->trans('ticket.error.category.7')][$this->translator->trans('ticket.error.category.74')] = 74; //performance ticket

        return $errorCategory;
    }

    public function errorType(): array
    {
        $errorType[$this->translator->trans('ticket.error.type.10')] = 10; //EFOR
        $errorType[$this->translator->trans('ticket.error.type.20')] = 20; //SOR
        $errorType[$this->translator->trans('ticket.error.type.30')] = 30; //OMC

        return $errorType;
    }
    public function kpiStatus(): array
    {
        $statusKpi[$this->translator->trans('ticket.kpistatus.10')] = 10;
        $statusKpi[$this->translator->trans('ticket.kpistatus.20')] = 20;

        return $statusKpi;
    }

    public function kpiPaDep1(): array
    {
        $kpi['normal outage'] = 10;
        $kpi['force majeure'] = 20;
        $kpi['tbd'] = 30;

        return $kpi;
    }

    public function kpiPaDep2(): array
    {
        $kpi['normal outage'] = 10;
        $kpi['force majeure'] = 20;
        $kpi['tbd'] = 30;

        return $kpi;
    }

    public function kpiPerformace(): array
    {
        $kpi['Sensor'] = 10;
        $kpi['PR'] = 20;
        $kpi['Power'] = 30;

        return $kpi;
    }

    public function kpiPaDep3(): array
    {
        return $this->errorType();
    }
    public function PRExcludeMethods(){
        $prMethod[$this->translator->trans('ticket.prmethod.10')] = 10;
        $prMethod[$this->translator->trans('ticket.prmethod.20')] = 20;
        return $prMethod;
    }
    public function scope(){
        $scope[$this->translator->trans('ticket.scope.10')] = 10;
        $scope[$this->translator->trans('ticket.scope.20')] = 20;
        $scope[$this->translator->trans('ticket.scope.30')] = 30;
        return $scope;
    }
}
