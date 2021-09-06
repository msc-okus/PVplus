<?php
use \koolreport\excel\Table;
//use \koolreport\widgets\koolphp\Table;

$headlines = $this->dataStore('headlines')->toArray()[0];
?>
<meta charset="UTF-8">
<meta name="keywords" content="">
<meta name="creator" content="green4net">
<meta name="subject" content="">
<meta name="title" content="">

<div sheet-name="Basic Values">
    <div>Basic Values</div>
    <div>
        <?php
        //
        Table::create([
            'dataSource'    => $this->dataStore('header'),
            'columns'       => [
                'PRDesign'              => [
                    'label'         => 'PR Design [%]',
                    'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                ],
                'PRgarantiert'          => [
                    'label'         => 'PR Garantiert [%]',
                    'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                ],
                'ExpectedEnergy'          => [
                    'label'         => 'Expected Energy [kWh]',
                    'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                ],
                'AbschlagTrafo'          => [
                    'label'         => 'Abschlag Trafoverlust [%]',
                    'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                ],
                'AbschlagGarantie'          => [
                    'label'         => 'Abschlag Garantie [%]',
                    'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                ],
                'kwPeak'          => [
                    'label'         => 'Gesamtleitung der Anlage [kWp]',
                    'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                ],
                'kwPeakPvSyst'          => [
                    'label'         => 'Gesamtleitung der Anlage PvSyst [kWp]',
                    'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                ],
                'startFac'          => [
                    'label'         => 'Start FAC',
                ],
                'endeFac'          => [
                    'label'         => 'Ende FAC',
                ],
                'startPac'          => [
                    'label'         => 'Start PAC',
                ],
            ],
        ]);
        ?>
    </div>
</div>
<div sheet-name="Forecast">
    <div>Forecast</div>
    <div>
        <?php
        // PR
        Table::create([
            'dataSource'    => $this->dataStore('forecast'),
            'columns'       => [
                'parameter'     => [
                    'label'         => 'Parameter',
                    'cssStyle'      => 'text-align:left',
                ],
                'value'         => [
                    'label'         => 'Value',
                    'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                ],
                'unit'          => [
                    'label'         => 'unit',
                    'cssStyle'      => 'text-align:left'
                ],
                'explanation'   => [
                    'label'         => 'Explanation',
                    'cssStyle'      => 'text-align:left',
                ],
            ],
        ]);
        ?>
    </div>
</div>
<div sheet-name="PLD">
    <div>PLD</div>
    <div>
        <?php
        //PLD
        Table::create([
            'dataSource'    => $this->dataStore('pld'),
        ]);
        ?>
    </div>
</div>
<div sheet-name="Monthly Values">
    <div>Monthly Values</div>
    <div>
        <?php
        //Monthly Values
        Table::create([
            'dataSource'    => $this->dataStore('main'),
        ]);
        ?>
    </div>
</div>
