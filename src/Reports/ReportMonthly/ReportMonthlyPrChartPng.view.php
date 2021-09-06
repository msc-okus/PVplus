<?php
use koolreport\widgets\google\ComboChart;

$headline = $this->params["headline"][0];
$anlagenid = $this->params["anlagenid"];

$params = $this->dataStores['ownparams']->toArray()[0];
$lineBreake = null;
$doubleLineBreake = null;
$useGridMeterDayData = $params['useGridMeterDayData'];

$dataourcePandRChart = $this->dataStores['daychartvalues'];

include_once __DIR__ . '/tablechart_fields.tmpl';

?>

<div class="grid-x grid-margin-x">
    <div class="cell">
        <h2>Performance Ratio <?php echo $headline["plant_name"].' ';?></h2>
        <?php
        ComboChart::create(array(
            'dataSource'    => $dataourcePandRChart,
            "columns"=>getPandRChartFields(' ', ' ', 'ä', $useGridMeterDayData),
            "options"=>array(
                "series"=> array(
                    0=> array("targetAxisIndex"=> 0),
                    1=> array("targetAxisIndex"=> 1),
                ),
                "vAxes"=>array(
                    0=> array("title"=> 'KWH'),
                    1=> array("title"=> '%')
                ),
            ),
            "max-width"=>"1000px",
            "width"=>"1000px",
            "colorScheme"=>array("#cc0000","#3fc828")
        ));
        ?>
    </div>
</div>





