<?php

use koolreport\widgets\koolphp\Table;
use koolreport\widgets\google\ComboChart;

$headline = $this->params["headline"][0];
$params = $this->dataStores['ownparams']->toArray()[0];

if ($params['doctype'] != 1) {
    $lineBreake = '<br>';
    $doubleLineBreake = '<br><br>';
    $auml = '&auml;';

} else {
    $lineBreake = ' ';
    $doubleLineBreake = ' ';
    $auml = 'ä';
}

$anlagenId                  = $params['anlagenId'];
$showAvailability           = $params['showAvailability'];
$showAvailabilitySecond     = $params['showAvailabilitySecond'];
$useGridMeterDayData        = $params['useGridMeterDayData'];
$useEvu                     = $params['useEvu'];
$showPvSyst                 = $params['showPvSyst'];
$showHeatAndTemperaturTable = $params['showHeatAndTemperaturTable'];
$reportCreationDate         = $params['reportCreationDate'];

$dataourceEP                = $this->dataStores['energyproduction'];
$dataourcePandR             = $this->dataStores['performanceratioandavailability'];
$dataourcePandRChart        = $this->dataStores['daychartvalues'];
$dataourceEandPChart        = $this->dataStores['daychartvalues'];
$dataourceDayValues         = $this->dataStores['dayvalues'];
$irradiationandtempvalues   = $this->dataStores['irradiationandtempvalues'];

include_once __DIR__ . '/tablechart_fields.tmpl';

?>
<html>
<head>
    <title>Monthly Report</title>
    <link href='/scss/report-epc.css' rel="stylesheet" type="text/css">
</head>

<div class="grid-x grid-margin-x">
    <div class="cell">
        <?php
        Table::create(array(
            'dataSource' => $dataourceEP,
            "columns" => getEPFields($lineBreake, $doubleLineBreake, $auml, $useGridMeterDayData, $showPvSyst, $useEvu),
            "cssClass" => array(
                "table" => "table-bordered table-striped table-hover"
            ),
            "max-width" => "2000px",
            "height" => "100%",
        ));
        ?>
    </div>
</div>

<div class="grid-x grid-margin-x">
    <div class="cell">
        <h2>Performance Ratio and Availability</h2>
        <?php
        Table::create(array(
            'dataSource' => $dataourcePandR,
            "columns" => getPandRFields($lineBreake, $doubleLineBreake, $auml, $showAvailability, $showAvailabilitySecond, $useGridMeterDayData, $showPvSyst, $useEvu),
            "cssClass" => array(
                "table" => "table-bordered table-striped table-hover"
            ),
            "max-width" => "2000px",
            "height" => "100%",
        ));
        ?>
    </div>
</div>

<div class="grid-x grid-margin-x">
    <div class="cell">
        <h2>Performance Ratio</h2>
        <?php
        ComboChart::create(array(
            'dataSource' => $dataourcePandRChart,
            "columns" => getPandRChartFields($lineBreake,$doubleLineBreake,$auml,$useGridMeterDayData),
            "options" => array(
                "series" => array(
                    0 => array("targetAxisIndex" => 0),
                    1 => array("targetAxisIndex" => 1),
                ),
                "vAxes" => array(
                    0 => array("title" => 'kWh'),
                    1 => array("title" => '%')
                ),
            ),
            "max-width" => "1000px",
            "width" => "1000px",
            "colorScheme" => array("#cc0000", "#3fc828")
        ));
        ?>
    </div>
</div>

<div class="grid-x grid-margin-x">
    <div class="cell">
        <h2>Energy Production</h2>
        <?php
        ComboChart::create(array(
            'dataSource' => $dataourceEandPChart,
            "columns" => getEandPChartFields($lineBreake,$doubleLineBreake,$auml,$useGridMeterDayData),
            "options" => array(
                "series" => array(
                    0 => array("targetAxisIndex" => 0),
                    1 => array("targetAxisIndex" => 1),
                ),
                "vAxes" => array(
                    0 => array("title" => 'KWH'),
                    1 => array("title" => 'Irradiation')
                ),
            ),
            "max-width" => "1000px",
            "width" => "1000px",
            "colorScheme" => array("#cc0000", "#fde72b"),
            "cssStyle" => "font-size:1.0em;",
        ));
        ?>
    </div>
</div>

<div class='break-before'></div>
<div class="grid-x grid-margin-x">
    <div class="cell">
        <h2>Day Values</h2>
        <?php
        Table::create([
            'dataSource' => $dataourceDayValues,
            'showHeader' => true,
            'columns' => getDayValuesields($lineBreake, $doubleLineBreake, $auml, $showAvailability, $showAvailabilitySecond, $useGridMeterDayData, $useEvu),
        ]);
        ?>
    </div>
</div>

<?php
    if($showAvailability == true || $showAvailabilitySecond == true) {
?>
<div class="grid-x grid-margin-x" style="margin-top: 2em">
    <div class="cell">
        <h2>Case5</h2>
        <?php
        Table::create([
            'dataSource'    => $this->dataStores['case5'],
            'showHeader'    => true,
            'columns'       => getCase5Fields($lineBreake,$doubleLineBreake,$auml),
            "options" => [
                "width"=>"1000px",
            ]
        ]);
        ?>
    </div>
</div>
<?php
    }
?>
<?php
    if($showHeatAndTemperaturTable == true){
?>
<div class='break-before'></div>
<div class="grid-x grid-margin-x">
    <div class="cell">
        <h2>Irradiation and Temperature in [Wh/m&sup2] and [&deg;C]</h2>
        <?php
        Table::create([
            'dataSource'    => $irradiationandtempvalues,
            'showHeader'    => true,
            'columns'       => getIandTFields($lineBreake,$doubleLineBreake,$auml,$irradiationandtempvalues[0]),
        ]);
        ?>
    </div>
</div>
<?php
    }
?>

<div class="grid-x grid-margin-x">
    <div class="cell">
        <h3>Legend</h3>
        <?php
        // Legende
        Table::create([
            'dataSource'    => $this->dataStores['legend'],
            'showHeader'    => true,
            'columns'        => getLegendFields($lineBreake,$doubleLineBreake,$auml),
        ]);
        ?>
    </div>
</div>
<div class="row" style="font-size:1.3em;">
    <p><b>Remarks: </b></p>
    <p>Generally, and especially on days with outage, the expected / actual difference indicates the lost production.</p>
</div>

<?php

if ($params['doctype'] != 1) {
    include_once __DIR__ . '/../views/view_pdf_header_footer.tmpl';
    echo getHeaderFooter($params,$headline["plant_power"]);
}
?>

</body>
</html>