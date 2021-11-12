<?php
use koolreport\widgets\koolphp\Table;
use koolreport\widgets\google\ComboChart;

$headlines = $this->dataStore('headlines')->toArray()[0];
?>
<html>
<head>
    <title>Goldbeck EPC - Yield Guarantee Report</title>
    <link href='/scss/report-epc.css' rel="stylesheet" type="text/css">
</head>
<body>
<div class="grid-x grid-margin-x">
    <div class="cell small-12">
        <h3>Basic Values</h3>
        <?php
            Table::create([
                'dataSource'    => $this->dataStore('header')->toArray(),
                'showHeader'    => true,
                'columns'       => [
                    'startFac'          => [
                        'label'         => 'Start FAC',
                    ],
                    'endeFac'          => [
                        'label'         => 'End FAC',
                    ],
                    'PRDesign'              => [
                        'label'         => 'PR Design<br>[%]',
                        'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                    ],
                    'PRgarantiert'          => [
                        'label'         => 'PR Guaranteed<br>[%]',
                        'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                    ],
                    'ExpectedEnergy'          => [
                        'label'         => 'Expected Energy<br>[kWh]',
                        'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                    ],
                    'ExpectedEnergyGuar'      => [
                        'label'         => 'Guaranteed Expected Energy<br>[kWh]',
                        'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                    ],
                    'AbschlagTrafo'          => [
                        'label'         => 'Abschlag Trafoverlust<br>[%]',
                        'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                    ],
                    'AbschlagGarantie'          => [
                        'label'         => 'Abschlag Garantie<br>[%]',
                        'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                    ],
                    'kwPeak'          => [
                        'label'         => 'Plant Size as build<br>[kWp]',
                        'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                    ],
                    'kwPeakPvSyst'          => [
                        'label'         => 'Plant Size by PVSYST<br>[kWp]',
                        'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                    ],
                ],
            ]);
        ?>
    </div>
</div>
<p>&nbsp;</p>
<div class="grid-x grid-margin-x">
    <div class="cell small-6">
        <h3>Ergebnis <?php echo str_replace('<br>', '&nbsp;', $this->dataStore('main')->toArray()[25]['month']); ?></h3>
        <?php
            Table::create([
                'dataSource'    => $this->dataStore('forecast24')->toArray(),
                'showHeader'    => true,
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
                        'label'         => 'Unit',
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
    <div class="cell small-6">
        <h3>Ergebnis <?php echo str_replace('<br>', '&nbsp;', $this->dataStore('main')->toArray()[26]['month']); ?></h3>
        <?php
        Table::create([
            'dataSource'    => $this->dataStore('forecast_real')->toArray(),
            'showHeader'    => true,
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
                    'label'         => 'Unit',
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

<div class="grid-x grid-margin-x">
    <div class="cell small-12">
        <h3>Yield: Percent Difference Calculation</h3>
        <?php
        ComboChart::create([
            'dataSource'    => $this->dataStore('graph'),
            'title'         => '',
            'options' => [
                "responsive"=>false,
                'chartArea' => [
                    'left' => 50,
                    'right' => 0,
                    'top'   => 10,
                    'bottom' => 100,
                ],
                'annotations'   => [
                    //'style'           => 'line',
                    'alwaysOutside'   => true,
                    'textStyle' => [
                        'fontSize' => 12,
                    ],
                ],
                'fontSize' => 12,
                'width' => 1500,
            ],
            'columns'       => [
                'month',
                'minusExpected' => [
                    'label'         => '',
                    'type'          => 'number',
                    'annotation'    => function($row) {return number_format($row['minusExpected'], 1, ',', '.') . ' %';},
                ],
            ],
        ]);
        ?>
    </div>
</div>

<div class='page-break'></div>


<div class="grid-x grid-margin-x">
    <div class="cell small-12">
        <h3>Monthly Values</h3>
        <?php
            Table::create([
                'dataSource'    => $this->dataStore('main')->toArray(),
                'showHeader'    => true,
                'cssClass'      =>[
                    'tr'        => function($row) {return $row['currentMonthClass'];}
                ],
                'columns' => [
                    'month' => [
                        'type'          => 'string',
                        'label'         => 'Month<br><br>',
                        'cssStyle'      => 'text-align:center'
                    ],
                    'days' => [
                        'type'          => 'string',
                        'label'         => 'Days<br><br>',
                        'cssStyle'      => 'text-align:center'
                    ],
                    'irradiation' => [
                        'type'          => 'number',
                        'label'         => 'Irradiation<br>weighted average<br>[kWh/m&sup2;]',
                        'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                    ],
                    'prDesign'  => [
                        'label'         => 'PR<sub><small>Design_M</small></sub><br><br>[%]',
                        'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                    ],
                    'ertragDesign' => [
                        'label'         => 'EGrid<sub><small>Design_M</small></sub><br><br>[kWh]',
                        'formatValue'   => function($value) {return number_format($value, 0, ',', '.');},
                    ],
                    'spezErtragDesign' => [
                        'label'         => 'specific<br>Yield<sub><small>Design_M</small></sub><br>[kWh/kWp]',
                        'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                    ],
                    'prGuar' => [
                        'label'         => 'PR<sub><small>(Guar_M)</small></sub><br><br>[%]',
                        'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                    ],
                    'prReal' => [
                        'label'         => 'PR<sub><small>Real_M</small></sub><br><br>[%]',
                        'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                    ],
                    'eGridReal'=> [
                        'label'         => 'EGrid<sub><small>Real_M</small></sub><br><br>[kWh]',
                        'formatValue'   => function($value) {return number_format($value, 0, ',', '.');},
                    ],
                    'spezErtrag'=> [
                        'label'         => 'specific<br>Yield<sub><small>Real_M</small></sub><br>[kWh/kWp]',
                        'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                    ],
                    'availability' => [
                        'label'         => 'Availability<br>[%]',
                        'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                    ],
                    'dummy' => [
                        'type'          => 'string',
                        'label'         => '',
                        'cssStyle'      => 'background-color: #767676;'
                    ],
                    'eGridReal-Design'=> [
                        'type'          => 'number',
                        'label'         => 'EGrid<sub><small>_Real_M</small></sub> -<br>EGrid<sub><small>_Design_M</small></sub><br>[kWh]',
                        'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                    ],
                    'prReal_prDesign' => [
                        'label'         => 'PR<sub><small>Real_M</small></sub> -<br>PR<sub><small>Design_M</small></sub><br>[%]',
                        'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                    ],
                    'eGridReal-Guar'=> [
                        'type'          => 'number',
                        'label'         => 'EGrid<sub><small>_Real_M</small></sub> -<br>EGrid<sub><small>_Guar_M</small></sub><br>[kWh]',
                        'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                    ],
                    'prReal_prGuar' => [
                        'label'         => 'PR<sub><small>Real_M</small></sub> -<br>PR<sub><small>Guar_M</small></sub><br>[%]',
                        'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                    ],
                    'prReal_prProg' => [
                        'label'         => 'PR<sub><small>Real_M</small></sub> /<br>PR<sub><small>Prog_M</small></sub><br>[%]',
                        'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                    ],
                    'anteil'=> [
                        'label'         => 'Ratio<br><br>[%]',
                        'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                    ],
                    'expectedErtrag' => [
                        'label'         => 'Expected Yield<br>[kWh]',
                        'formatValue'   => function($value) {return number_format($value, 0, ',', '.');},
                    ],
                    'guaranteedExpexted' => [
                        'label'         => 'Guranteed<br>Expected Yield<br>[kWh]',
                        'formatValue'   => function($value) {return number_format($value, 0, ',', '.');},
                    ],
                    'minusExpected' => [
                        'label'         => 'EGrid / <br>Gura. Exp. Yield<br>[%]',
                        'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                    ],
                ],
            ]);
        ?>
    </div>
</div>

<div class='page-break'></div>

<div class="grid-x grid-margin-x">
    <div class="cell">
        <h3>Legend</h3>
        <?php
        // Legende
        Table::create([
            'dataSource'    => $this->dataStore('legend'),
            'showHeader'    => true,
            'columns'        => [
                'title'         => [
                    'label'     => 'Title',
                    'cssStyle'  => 'text-align:left',
                ],
                'unit'          => [
                    'label'     => 'Unit',
                    'cssStyle'  => 'text-align:left',
                ],
                'description'   => [
                    'label'     => 'Description',
                    'cssStyle'  => 'text-align:left',
                ],
                'source'   => [
                    'label'     => 'Source',
                    'cssStyle'  => 'text-align:left',
                ],
            ],
        ]);
        ?>
    </div>
</div>
<div class="grid-x grid-margin-x">
    <div class="cell">
        <h3>Remarks</h3>
        <?php echo $headlines['epcNote']?>
    </div>
</div>

<header>
    <div style="width: 800px; margin: 15px 30px 0;">
        <div style="float: left; padding-right: 50px;"><img src="https://dev.g4npvplus.net/custImg/Goldbeck/GBS-logo.png" width="150px" ></div>
        <div style="font-size: 14px !important; font-weight: bold; float: left; text-align: center; padding-top: 10px;">
            <? echo $headlines['projektNr'].' '.$headlines['anlage'].' <span style="font-size: 10px !important; font-weight: normal;">('.number_format($headlines['kwpeak'], 2, ',', '.').' kWp)</span></span>' ?>
        </div>
        <div style="float: right;"><img src="https://dev.g4npvplus.net/images/green4net.jpg" width="100px" ></div>

    </div>
</header>

<footer>
    <div style="margin: 0px 30px;">
        <div style="font-size:9px !important; width: 750px !important;">
            Page: <span class="pageNumber"></span> of <span class="totalPages"></span>
            <span style="float: right !important;">Creation date: <?php echo $headlines['reportCreationDate'];?></span>
        </div>
    </div>
</footer>
</body>
</html>
