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
        $reportStati[0] = 'final';
        $reportStati[1] = 'final FAC';
        $reportStati[3] = 'under observation';
        $reportStati[5] = 'proof reading';
        $reportStati[9] = 'archive (g4n only)';
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
        $spriority[$this->translator->trans('ticket.priority.10')] = 10;
        $spriority[$this->translator->trans('ticket.priority.20')] = 20;
        $spriority[$this->translator->trans('ticket.priority.30')] = 30;
        $spriority[$this->translator->trans('ticket.priority.40')] = 40;


        return $spriority;
    }

    public function errorCategorie(): array
    {
        $errorCategory[$this->translator->trans('ticket.error.category.10')] = 10;
        $errorCategory[$this->translator->trans('ticket.error.category.20')] = 20;
        $errorCategory[$this->translator->trans('ticket.error.category.30')] = 30;
        $errorCategory[$this->translator->trans('ticket.error.category.40')] = 40;
        $errorCategory[$this->translator->trans('ticket.error.category.50')] = 50;
        $errorCategory[$this->translator->trans('ticket.error.category.60')] = 60;
        return $errorCategory;
    }

    public function errorType(): array
    {
        $errorType[$this->translator->trans('ticket.error.type.10')] = 10;
        $errorType[$this->translator->trans('ticket.error.type.20')] = 20;
        $errorType[$this->translator->trans('ticket.error.type.30')] = 30;

        return $errorType;
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

    public function kpiPaDep3(): array
    {
        return $this->errorType();
    }
}
