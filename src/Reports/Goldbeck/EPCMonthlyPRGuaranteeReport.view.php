<?php
use koolreport\widgets\google\ComboChart;
use koolreport\widgets\koolphp\Table;

$headlines = $this->dataStore('headlines')->toArray()[0];

?>
<html>
<head>
    <title>Goldbeck EPC - PR Guarantee Report</title>
    <link href='/scss/report-epc.css' rel="stylesheet" type="text/css">
</head>
<body>
<div class="grid-x grid-margin-x">
    <div class="cell">
        <h2><?php echo $headlines['main_headline']?></h2>
        <h3>Basic Values</h3>
        <?php
            Table::create([
                'dataSource' => $this->dataStore('header')->toArray(),
                'showHeader' => true,
                'columns' => [
                    'startFac' => [
                        'label' => 'Start FAC',
                    ],
                    'endeFac' => [
                        'label' => 'End FAC',
                    ],
                    'pld' => [
                        'type' => 'number',
                        'label' => 'PLD [EUR/kWh]',
                        'formatValue' => function ($value) {return number_format($value, 8, ',', '.'); },
                    ],
                    'PRDesign' => [
                        'type' => 'number',
                        'label' => 'PR design [%]',
                        'formatValue' => function ($value) {return number_format($value, 2, ',', '.'); },
                    ],
                    'PRgarantiert' => [
                        'type' => 'number',
                        'label' => 'PR guaranteed [%]',
                        'formatValue' => function ($value) {return number_format($value, 2, ',', '.'); },
                    ],
                    'Risikoabschlag' => [
                        'type' => 'number',
                        'label' => 'Risk discount [%]',
                        'formatValue' => function ($value) {return number_format($value, 2, ',', '.'); },
                    ],
                    'AnnualDegradation' => [
                        'type' => 'number',
                        'label' => 'Annual Degradation [%]',
                        'formatValue' => function ($value) {return number_format($value, 2, ',', '.'); },
                    ],
                    'kwPeak' => [
                        'type' => 'number',
                        'label' => 'Plant size as build [kWp]',
                        'formatValue' => function ($value) {return number_format($value, 2, ',', '.'); },
                    ],
                    'kwPeakPvSyst' => [
                        'type' => 'number',
                        'label' => 'Plant size by PVSYST [kWp]',
                        'formatValue' => function ($value) {return number_format($value, 2, ',', '.'); },
                    ],
                ],
            ]);
        ?>
    </div>
</div>
<div class="grid-x grid-margin-x">
    <div class="cell small-6">
        <?php if ($headlines['reportStatus'] != 1) {?>
        <h3>PR Forecast <small><?php echo $this->dataStore('forecast')->toArray()[0]['forecastDateText']; ?></small></h3>
        <?php
            Table::create([
                'dataSource' => $this->dataStore('forecast'),
                'showHeader' => true,
                'columns' => [
                    'PRDiffYear' => [
                        'label' => 'PR<sub><small>Prog</small></sub> - PR<sub><small>Guar</small></sub> [%]',
                        'formatValue' => function ($value) {return number_format($value, 2, ',', '.'); },
                    ],
                    'message' => [
                        'label' => '',
                    ],
                    'pld' => [
                        'label' => 'Total PLD [EUR]',
                        'type' => 'number',
                        'formatValue' => function ($value) {return number_format($value, 2, ',', '.'); },
                    ],
                ],
            ]);
        ?>
        <?php  } ?>
    </div>
    <div class="cell small-6">
        <h3>PR Real <small><?php echo $this->dataStore('forecast_real')->toArray()[0]['forecastDateText']; ?></small></h3>
        <?php
            Table::create([
                'dataSource' => $this->dataStore('forecast_real'),
                'showHeader' => true,
                'columns' => [
                    'PRDiffYear' => [
                        'label' => 'PR<sub><small>Real</small></sub> - PR<sub><small>Guar</small></sub> [%]',
                        'formatValue' => function ($value) {return number_format($value, 2, ',', '.'); },
                    ],
                    'availability' => [
                        'label' => 'Availability',
                        'formatValue' => function ($value) {return number_format($value, 2, ',', '.'); },
                    ],
                    'message' => [
                        'label' => '',
                    ],
                    'pld' => [
                        'label' => 'Total PLD [EUR]',
                        'type' => 'number',
                        'formatValue' => function ($value) {return number_format($value, 2, ',', '.'); },
                    ],
                ],
            ]);
        ?>
    </div>
</div>
<div class="grid-x grid-margin-x">
    <div class="cell small-6">
        <?php
        $array = $this->dataStore('formel')->toArray()[0];
        echo match ($array['algorithmus']) {
            'Groningen' => "<img src='/images/temp/prFormelGroningen.jpg' style='max-width: 400px'>",
            'Veendam'   => "<img src='/images/temp/prFormelVeendam.png' style='max-width: 400px'>",
            'Lelystad'  => "<img src='/images/temp/prFormelLelystad.png' style='max-width: 400px'>",
            default     => "<img src='/images/temp/prFormelStandard.png' style='max-width: 400px'>",
        };
        ?>
    </div>
    <div class="cell small-6">
        <?php
            $array = $this->dataStore('formel')->toArray()[0];
            switch ($array['algorithmus']) {
                case 'Groningen':
                    $html = '';
                    break;
                case 'Veendam':
                    $html = "<table>
                                    <tr>
                                        <td style='text-align: center'>".number_format($array['eGridReal'], 2, ',', '.')." kWh</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td style='text-align: center'>----------------------------</td>
                                        <td style='text-align: center'>x</td>
                                        <td style='text-align: center'>100</td>
                                        <td style='text-align: center'>=</td>
                                        <td style='text-align: center'>".number_format($array['prReal'], 2, ',', '.')."%</td>
                                    </tr>
                                    <tr>
                                        <td style='text-align: center'>".number_format($array['theoPower'], 2, ',', '.').' kWh * '.number_format($array['availability'] / 100, 4, ',', '.').'</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                </table>';
                    break;
                case 'Lelystad':
                    $html = "<table style='text-align: center'>
                                    <tr>
                                        <td style='text-align: center'>".number_format($array['eGridReal'], 2, ',', '.')." kWh</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td style='text-align: center'>----------------------------</td>
                                        <td style='text-align: center'>x</td>
                                        <td style='text-align: center'>100</td>
                                        <td style='text-align: center'>=</td>
                                        <td style='text-align: center'>".number_format($array['prReal'], 2, ',', '.')."%</td>
                                    </tr>
                                    <tr>
                                        <td style='text-align: center'>".number_format($array['theoPower'], 2, ',', '.').' kWh</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                </table>';
                    break;
                default:
                    $html = "<table>
                                    <tr>
                                        <td style='text-align: center'>".number_format($array['eGridReal'], 2, ',', '.')." kWh</td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td style='text-align: center'>------------------------</td>
                                        <td style='text-align: center'>=</td>
                                        <td style='text-align: center'>".number_format($array['prReal'], 2, ',', '.')." %</td>
                                    </tr>
                                    <tr>
                                        <td style='text-align: center'>".number_format($array['theoPower'], 2, ',', '.').' kWh</td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                </table>';
            }
            echo $html;
        ?>
    </div>
</div>
<div class="grid-x grid-margin-x">
    <div class="cell small-3">
        <?php if ($this->dataStore('forecast_real')->toArray()[0]['pld'] > 0) {?>
            <h3>PLD</h3>
            <?php
                Table::create([
                    'dataSource' => $this->dataStore('pld')->toArray(),
                    'showHeader' => true,
                    'columns' => [
                        'year' => [
                            'label' => 'Year',
                        ],
                        'eLoss' => [
                            'label' => 'E loss [kWh]',
                            'formatValue' => function ($value) {return number_format($value, 2, ',', '.'); },
                        ],
                        'pld' => [
                            'label' => 'net present PLD [EUR]',
                            'formatValue' => function ($value) {return number_format($value, 2, ',', '.'); },
                        ],

                        '{others}' => [
                            'type' => 'number',
                            'formatValue' => function ($value) {return number_format($value, 2, ',', '.'); },
                        ],
                    ],
                ]);
            ?>
        <?php } ?>
    </div>
    <div class="cell small-9" style="max-height: 350px; overflow: hidden;">
        <h3>Difference PR<sub><small>prog/real</small></sub> - PR<sub><small>Guar</small></sub> <small>(<?php echo $this->dataStore('forecast')->toArray()[0]['forecastDateText']; ?>)</small></h3>
        <?php
            ComboChart::create([
                'dataSource' => $this->dataStore('graph'),
                'title' => '',
                'options' => [
                    'chartArea' => [
                        'left' => 50,
                        'right' => 0,
                        'top' => 10,
                        'bottom' => 50,
                    ],
                    'annotations' => [
                        'textStyle' => [
                            'fontSize' => 10,
                        ],
                        'color' => '#4d4d4d',
                    ],
                    'fontSize' => 10,
                    'width' => 750,
                    'height' => 310,
                ],
                'columns' => [
                    'month',
                    'prReal_prGuar' => [
                        'label' => 'PR real - PR guranteed [%]',
                        'type' => 'number',
                        'annotation' => function ($row) {return number_format($row['prReal_prGuar'], 1, ',', '.').' %'; },
                    ],
                ],
            ]);
        ?>
    </div>
</div>

<div class='page-break'></div>

<div class="grid-x grid-margin-x">
    <div class="cell">
        <h3>Monthly Values</h3>
        <?php
            match ($array['algorithmus']) {
                'Lelystad'  => $excludedColumns[] = '',//'availability',
                default => $excludedColumns[] = ''
            };
            if ($headlines['reportStatus'] == 1) {
                $excludedColumns[] = 'prReal_prDesign';
                $excludedColumns[] = 'dummy';
                $excludedColumns[] = 'eGridReal-Design';
                $excludedColumns[] = 'prReal_prGuar';
                $excludedColumns[] = 'prReal_prProg';
                $excludedColumns[] = 'anteil';
                $excludedColumns[] = 'specPowerGuar';
            } else {
                $excludedColumns = [];
            }
            Table::create([
                'excludedColumns'   => $excludedColumns,
                'dataSource'        => $this->dataStore('main')->toArray(),
                'showHeader'        => true,
                'cssClass'          => [
                    'tr' => function ($row) {return $row['currentMonthClass']; },
                ],
                'columns'           => [
                    'month' => [
                        'type' => 'string',
                        'label' => 'Month  <br><br>',
                        'cssStyle' => 'text-align:center',
                    ],
                    'days' => [
                        'type' => 'string',
                        'label' => 'Days<br><br>',
                        'cssStyle' => 'text-align:center',
                    ],
                    'irradiation' => [
                        'type' => 'number',
                        'label' => 'Irradiation<br>weighted average<br>[kWh/m&sup2;]',
                        'formatValue' => function ($value) {return number_format($value, 2, ',', '.'); },
                    ],
                    'prDesign' => [
                        'type' => 'number',
                        'label' => 'PR<sub><small>_Design_M</small></sub><br><br>[%]',
                        'formatValue' => function ($value) {return number_format($value, 2, ',', '.'); },
                    ],
                    'ertragDesign' => [
                        'type' => 'number',
                        'label' => 'EGrid<sub><small>_Design_M</small></sub><br><br>[kWh]',
                        'formatValue' => function ($value) {return number_format($value, 0, ',', '.'); },
                    ],
                    'spezErtragDesign' => [
                        'type' => 'number',
                        'label' => 'specif.<br>Yield<sub><small>_Design_M</small></sub><br>[kWh/kWp]',
                        'formatValue' => function ($value) {return number_format($value, 2, ',', '.'); },
                    ],
                    'prGuar' => [
                        'type' => 'number',
                        'label' => 'PR<sub><small>_Guar_M</small></sub><br><br>[%]',
                        'formatValue' => function ($value) {return number_format($value, 2, ',', '.'); },
                    ],
                    'prReal' => [
                        'type' => 'number',
                        'label' => 'PR<sub><small>_Real_M</small></sub><br><br>[%]',
                        'formatValue' => function ($value) {return number_format($value, 2, ',', '.'); },
                    ],
                    'eGridReal' => [
                        'type' => 'number',
                        'label' => 'EGrid<sub><small>_Real_M</small></sub><br><br>[kWh]',
                        'formatValue' => function ($value) {return number_format($value, 0, ',', '.'); },
                    ],
                    'spezErtrag' => [
                        'type' => 'number',
                        'label' => 'specif.<br>Yield<sub><small>_Real_M | Prog_M</small></sub><br>[kWh/kWp]',
                        'formatValue' => function ($value) {return number_format($value, 2, ',', '.'); },
                    ],
                    'prReal_prDesign' => [
                        'type' => 'number',
                        'label' => 'PR<sub><small>_Real_M</small></sub> -<br>PR<sub><small>_Design_M</small></sub><br>[%]',
                        'formatValue' => function ($value) {return number_format($value, 2, ',', '.'); },
                    ],
                    'availability' => [
                        'type' => 'number',
                        'label' => 'Availability<br>[%]',
                        'formatValue' => function ($value) {return number_format((float) $value, 2, ',', '.'); },
                    ],
                    'dummy' => [
                        'type' => 'string',
                        'label' => '',
                        'cssStyle' => 'background-color: #767676;',
                    ],
                    'eGridReal-Design' => [
                        'type' => 'number',
                        'label' => 'EGrid<sub><small>_Real_M</small></sub> -<br>EGrid<sub><small>_Design_M</small></sub><br>[kWh]',
                        'formatValue' => function ($value) {return number_format($value, 2, ',', '.'); },
                    ],
                    'prReal_prGuar' => [
                        'type' => 'number',
                        'label' => 'PR<sub><small>_Real_M</small></sub> - <br>PR<sub><small>_Guar_M</small></sub><br>[%]',
                        'formatValue' => function ($value) {return number_format($value, 2, ',', '.'); },
                    ],
                    'prReal_prProg' => [
                        'type' => 'number',
                        'label' => 'PR<sub><small>Real_M</small></sub> / <br>PR<sub><small>Prog_M</small></sub><br>[%]',
                        'formatValue' => function ($value) {return number_format($value, 2, ',', '.'); },
                    ],
                    'anteil' => [
                        'type' => 'number',
                        'label' => 'Ratio<br><br>[%]',
                        'formatValue' => function ($value) {return number_format((float) $value, 2, ',', '.'); },
                    ],
                    'specPowerGuar' => [
                        'type' => 'number',
                        'label' => 'spec.<br>Yield<sub><small>_Guar_M</small></sub><br>[kWh/kWp]',
                        'formatValue' => function ($value) {return number_format($value, 2, ',', '.'); },
                    ],
                    /*
                    'specPowerRealProg' => [
                        'type'          => 'number',
                        'label'         => 'spec.<br>Yield<sub><small>_Guar_M / Prog_M</small></sub><br>[kWh/kWp]',
                        'formatValue'   => function($value) {return number_format($value, 2, ',', '.');},
                    ],
                    */
                ]
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
                'dataSource' => $this->dataStore('legend'),
                'showHeader' => true,
                'columns' => [
                    'title' => [
                        'label' => 'Title',
                        'cssStyle' => 'text-align:left',
                    ],
                    'unit' => [
                        'label' => 'Unit',
                        'cssStyle' => 'text-align:left',
                    ],
                    'description' => [
                        'label' => 'Description',
                        'cssStyle' => 'text-align:left',
                    ],
                    'source' => [
                        'label' => 'Source',
                        'cssStyle' => 'text-align:left',
                    ],
                ],
            ]);
        ?>
    </div>
</div>
<div class="grid-x grid-margin-x">
    <div class="cell">
        <h3>Remarks</h3>
        <?php echo $headlines['epcNote']; ?>
    </div>
</div>
<header>
    <div style="width: 800px; margin: 15px 30px 0;">
        <div style="float: left; padding-right: 50px;"><img src="https://dev.g4npvplus.net/custImg/Goldbeck/GBS-logo.png" width="150px" ></div>
        <div style="font-size: 14px !important; font-weight: bold; float: left; text-align: center; padding-top: 10px;">
            <?php echo $headlines['projektNr'].' '.$headlines['anlage'].' <span style="font-size: 10px !important; font-weight: normal;">('.number_format($headlines['kwpeak'], 2, ',', '.').' kWp)</span></span>'; ?>
        </div>
        <div style="float: right;"><img src="https://dev.g4npvplus.net/images/green4net.jpg" width="100px" ></div>

    </div>
</header>

<footer>
    <div style="margin: 0px 30px;">
        <div style="font-size:9px !important; width: 750px !important;">
            Page: <span class="pageNumber"></span> of <span class="totalPages"></span>
            <span style="float: right !important;">Creation date: <?php echo $headlines['reportCreationDate']; ?></span>
        </div>
    </div>
</footer>
</body>
</html>
