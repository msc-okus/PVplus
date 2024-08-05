<?php

namespace App\Helper;

trait PVPNameArraysTrait
{

    public static function importTypes(): array
    {
        return [
            'standart'              => 'standart',
            'With Stringboxes'      => 'withStringboxes',
            'FTP Push'      => 'ftpPush',
        ];
    }

    public static function delayedDataValus(): array
    {
        return [
            '0' => '0',
            '1' => '1',
            '2' => '2',
            '3' => '3',
            '4' => '4',
            '5' => '5',
            '6' => '6',
            '7' => '7',
            '8' => '8',
            '9' => '9',
            '10' => '10',
            '11' => '11',
            '12' => '12',
            '13' => '13',
            '14' => '14',
            '15' => '15',
            '16' => '16',
            '17' => '17',
            '18' => '18',
            '19' => '19',
            '20' => '20',
            '21' => '21',
            '22' => '22',
            '23' => '23',
            '24' => '24',
        ];
    }

    public static function sensorTypes(): array
    {
        return [
            'pyranometer'       => 'pyranometer',
            'si-modul'          => 'si-modul',
            'temperature'       => 'temperature',
            'wind-speed'        => 'wind speed',
            'wind-direction'    => 'wind direction',

        ];
    }

    public static function sensorOriantation(): array
    {
        return [
            'modul'         => 'modul',
            'horizontal'    => 'horizontal',
            'modul-east'    => 'modul-east',
            'modul-west'    => 'modul-west',
        ];
    }

    public static function virtualSensors(): array
    {
        $virtualSensors['irradiation'] = [
            'irr'       => 'irr',
            'irr-east'  => 'irr-east',
            'irr-west'  => 'irr-west',
            'irr-hori'  => 'irr-hori',
        ];
        $virtualSensors['temperature'] = [
            'temp-ambient'      => 'temp-ambient',
            'temp-modul'        => 'temp-modul',
            'temp-modul-nrel'   => 'temp-modul-nrel',
            'temp-inverter'     => 'temp-inverter',
        ];
        $virtualSensors['wind'] = [
            'wind-speed'        => 'wind-speed',
            'wind-direction'    => 'wind-direction',
        ];

        $virtualSensors['not-used'] = 'not used';

        return $virtualSensors;
    }

    public static function prFormulars(): array
    {
        return [
            'No Cust PR (EnergyProduced / Theo.Energy) | IEC'   => 'no', // IEC61724-1:2021 Kapitel 14.3.1
            'EnergyProduced / (Theo.Energy * deg)'              => 'IEC_with_deg', // Basis IEC61724-1:2021 Kapitel 14.3.1 + Degradation
            'EnergyProduced / (Theo.Energy * PA)'               => 'Veendam', // IEC_with_PA // Basis IEC61724-1:2021 Kapitel 14.3.1 + Gewichtet mit PA (PlantAvailability)
            'EnergyProduced / (Theo.Energy * Ft) | IEC or NREL' => 'TempCorrNREL', //IEC_tempCorr // IEC61724-1:2021 Kapitel 14.3.2 und 14.3.3
            'EnergyProduced / (Theo.Energy * Ft * deg) | IEC'   => 'IEC61724-1:2021', // IEC_tempCorr_deg // Basis IEC61724-1:2021 Kapitel 14.3.2 und 14.3.3 + degradation
            'Lelystad (deprecated)'                             => 'Lelystad', // Sonder Lösung mit Berechnung der Cell Temp nach NREL
            'Ladenburg (not ready)'                             => 'Ladenburg',  // solte eine der esten 5 sein
            'Doellen (not ready)'                               => 'Doellen', // solte eine der esten 5 sein
            'Groningen (deprecated)'                            => 'Groningen', // Sonder Lösung
        ];
    }

    public static function paFormulars(): array
    {
        return [
            'ti / (titheo - tiFM)'  => 1,
            'ti / titheo'           => 2,
            '(ti + tiFM) / titheo'  => 3,
        ];
    }

    public static function yearsArray(): array
    {
        return [
            2019 => 2019,
            2020 => 2020,
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

    /**
     * alertType entspricht dem Fehler Typ der Anlage / Inverter / Sensor
     *  1: PA Tickets (Availability) | Gruppe
     * 10: Data Gap
     * 20: Inverter Error
     * 30: Grid Error
     * 40: Weather
     * 50: External Control (PPC, ...)
     * 60: Power/Expected Error
     *  7: Performance Tickets | Gruppe
     * 70: Exclude Sensors
     * 71: Replace Sensors
     * 72: Exclude from PR/Energy
     * 73: Replace Energy (Irr)
     * 74: Correct Energy
     */
    public function
    errorCategorie(bool $performanceTickets = true, bool $paTickets = true, bool $addGroup = false, $addG4NCategory = false): array
    {
        if ($paTickets) {
            if ($addGroup) $errorCategory[$this->translator->trans('ticket.error.category.1')][$this->translator->trans('ticket.error.category.1all')]  =  1;
            $errorCategory[$this->translator->trans('ticket.error.category.1')][$this->translator->trans('ticket.error.category.10')] = 10; //data gap
            $errorCategory[$this->translator->trans('ticket.error.category.1')][$this->translator->trans('ticket.error.category.20')] = 20; //inverter error
        }
        $errorCategory[$this->translator->trans('ticket.error.category.30')] = 30; //grid error
        $errorCategory[$this->translator->trans('ticket.error.category.40')] = 40; //weather
        $errorCategory[$this->translator->trans('ticket.error.category.50')] = 50; //external control
        $errorCategory[$this->translator->trans('ticket.error.category.60')] = 60; //power/expected error
        if ($performanceTickets) {
            if ($addGroup) $errorCategory[$this->translator->trans('ticket.error.category.7')][$this->translator->trans('ticket.error.category.7all')]  =  7;
            $errorCategory[$this->translator->trans('ticket.error.category.7')][$this->translator->trans('ticket.error.category.70')] = 70; //Exclude Sensors
            $errorCategory[$this->translator->trans('ticket.error.category.7')][$this->translator->trans('ticket.error.category.71')] = 71; //Replace Sensors
            $errorCategory[$this->translator->trans('ticket.error.category.7')][$this->translator->trans('ticket.error.category.72')] = 72; //Exclude from PR
            $errorCategory[$this->translator->trans('ticket.error.category.7')][$this->translator->trans('ticket.error.category.73')] = 73; //Replace Energy
            $errorCategory[$this->translator->trans('ticket.error.category.7')][$this->translator->trans('ticket.error.category.74')] = 74; //Correct Energy
        }
        if ($addG4NCategory) {
            if ($addGroup) $errorCategory[$this->translator->trans('ticket.error.category.9')][$this->translator->trans('ticket.error.category.9all')]  =  9;
            $errorCategory[$this->translator->trans('ticket.error.category.9')][$this->translator->trans('ticket.error.category.91')] = 91; //internal ticket
            $errorCategory[$this->translator->trans('ticket.error.category.9')][$this->translator->trans('ticket.error.category.92')] = 92; //internal ticket
            $errorCategory[$this->translator->trans('ticket.error.category.9')][$this->translator->trans('ticket.error.category.93')] = 93; //internal ticket

        }
        $errorCategory[$this->translator->trans('ticket.error.category.100')] = 100;
        return $errorCategory;
    }
    /** @deprecated  try to replace with 'errorCategorie(bool $performanceTickets = true, bool $paTickets = true, bool $addGroup = false, $addG4NCategory = false)'
     * Not finaly ready need some addional stuff for ticket search form
     * */
    public function listAllErrorCategorie($isG4N): array
    {
        $errorCategory[$this->translator->trans('ticket.error.category.10')] = 10; //data gap
        $errorCategory[$this->translator->trans('ticket.error.category.20')] = 20; //inverter error
        $errorCategory[$this->translator->trans('ticket.error.category.30')] = 30; //grid error
        $errorCategory[$this->translator->trans('ticket.error.category.40')] = 40; //weather
        $errorCategory[$this->translator->trans('ticket.error.category.50')] = 50; //external control
        $errorCategory[$this->translator->trans('ticket.error.category.60')] = 60; //power/expected error
        $errorCategory[$this->translator->trans('ticket.error.category.7')]  =  7;
        $errorCategory[$this->translator->trans('ticket.error.category.70')] = 70; //performance ticket
        $errorCategory[$this->translator->trans('ticket.error.category.71')] = 71; //performance ticket
        $errorCategory[$this->translator->trans('ticket.error.category.72')] = 72; //performance ticket
        $errorCategory[$this->translator->trans('ticket.error.category.73')] = 73; //performance ticket
        $errorCategory[$this->translator->trans('ticket.error.category.74')] = 74; //performance ticket
        if($isG4N) {
            $errorCategory[$this->translator->trans('ticket.error.category.9')] = 9;
            $errorCategory[$this->translator->trans('ticket.error.category.91')] = 91; //internal ticket
            $errorCategory[$this->translator->trans('ticket.error.category.92')] = 92; //internal ticket
            $errorCategory[$this->translator->trans('ticket.error.category.93')] = 93; //internal ticket
        }
        $errorCategory[$this->translator->trans('ticket.error.category.100')] = 100;
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
