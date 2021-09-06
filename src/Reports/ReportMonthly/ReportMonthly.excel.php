<?php
use \koolreport\excel\Table;
use \koolreport\excel\PieChart;
use \koolreport\excel\BarChart;
use \koolreport\excel\LineChart;
use \koolreport\excel\ComboChart;

$sheet1 = "Data";
$params = $this->dataStores['ownparams']->toArray()[0];

$lineBreake = ' ';
$doubleLineBreake = ' ';
$auml = 'ä';

$anlagenId = $params['anlagenId'];
$showAvailability = $params['showAvailability'];
$showAvailabilitySecond = $params['showAvailabilitySecond'];
$useGridMeterDayData = $params['useGridMeterDayData'];
$showPvSyst = $params['showPvSyst'];
$showHeatAndTemperaturTable = $params['showHeatAndTemperaturTable'];
$useEvu = $params['useEvu'];
$dataourceEP = $this->dataStores['energyproduction'];
$dataourcePandR = $this->dataStores['performanceratioandavailability'];
$dataourceDayValues = $this->dataStores['dayvalues'];
$irradiationandtempvalues = $this->dataStores['irradiationandtempvalues'];

include_once __DIR__ . '/tablechart_fields.tmpl';

?>
<meta charset="UTF-8">
<meta name="description" content="Monthly Reports">
<meta name="keywords" content="Excel,HTML,CSS,XML,JavaScript">
<meta name="creator" content="green4net">
<meta name="subject" content="Report">
<meta name="title" content="Report">


<div sheet-name="<?php echo $sheet1; ?>">
    <div>Energy Production</div>
    <div>
        <?php
        Table::create([
            'dataSource'    => $dataourceEP,
            'showHeader'    => true,
            "columns" => getEPFields($lineBreake,$doubleLineBreake,$auml,$useGridMeterDayData,$showPvSyst, $useEvu)
        ]);
        ?>
    </div>
    <div>&nbsp;</div>
    <div>
        Performance Ratio and Availability
    </div>
    <div>
        <?php
        Table::create([
            'name' => 'Performance Ratio and Availability',
            'dataSource'    => $dataourcePandR,
            "columns" => getPandRFields($lineBreake,$doubleLineBreake,$auml,$showAvailability,$showAvailabilitySecond,$useGridMeterDayData,$showPvSyst,$useEvu),
        ]);
        ?>
    </div>
    <div>&nbsp;</div>
    <div>
        <h2>Day Values</h2>
    </div>
    <div>
        <?php
        Table::create([
            'name' => 'Day Values',
            'dataSource'    => $dataourceDayValues,
            'columns' => getDayValuesields($lineBreake,$doubleLineBreake,$auml,$showAvailability,$showAvailabilitySecond,$useGridMeterDayData,$useEvu),
        ]);
        ?>
    </div>
    <?php
        if($showHeatAndTemperaturTable == true){
    ?>
        <div>&nbsp;</div>
        <div>
            <h2>Irradiation and Temperature in [Wh/qm] and [°C]</h2>
        </div>
        <div>
            <?php
            Table::create([
                'name' => 'Irradiation and Temperature',
                'dataSource'    => $this->dataStores['irradiationandtempvalues'],
                'columns'       => getIandTFields($lineBreake,$doubleLineBreake,$auml,$irradiationandtempvalues[0]),
            ]);
            ?>
        </div>
    <?php
        }
    ?>

    <div>&nbsp;</div>
    <div>
        <h2>Case5</h2>
    </div>
    <div>
        <?php
        Table::create([
            'name' => 'Case5',
            'dataSource'    => $this->dataStores['case5'],
            'columns'       => getCase5Fields($lineBreake,$doubleLineBreake,$auml),
        ]);
        ?>
    </div>
</div>