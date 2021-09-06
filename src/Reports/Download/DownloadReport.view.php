<?php

use koolreport\widgets\koolphp\Table;
use koolreport\widgets\google\ComboChart;

use Hisune\EchartsPHP\ECharts;
use \Hisune\EchartsPHP\Doc\IDE\Series;
use \Hisune\EchartsPHP\Config;

$params = $this->dataStores['params']->toArray()[0];

$showAvailability = $params['showAvailability'];
$showAvailabilitySecond = $params['showAvailabilitySecond'];
$useGridMeterDayData = $params['useGridMeterDayData'];
$formatBody = $params['formatBody'];
$plantPower = $params['plant_power'];

if($params['doctype'] != 'excel'){
    $lineBreake = '<br>';
    $doubleLineBreake = '<br><br>';
    $gradCelsius = '&deg;';
    $durchschnitt = '&Oslash;';
}else{
    $lineBreake = ' ';
    $doubleLineBreake = ' ';
    $gradCelsius = '°';
    $durchschnitt = 'Ø';
}
?>
<html>
<head>
    <title>Goldbeck Download</title>
    <link href='https://dev.g4npvplus.net/scss/report-epc.css' rel="stylesheet" type="text/css">
</head>
<body style="margin:<?php echo $formatBody; ?>">
<script src="https://dev.g4npvplus.net/echarts/echarts.min.js" type="text/javascript"></script>
<script src="https://dev.g4npvplus.net/echarts/theme/royal.js" type="text/javascript"></script>
<script src="https://dev.g4npvplus.net/echarts/theme/sakura.js" type="text/javascript"></script>
<?php
include_once __DIR__ . '/table_fields_downloads.tmpl';
if($params['tableType'] == 'default'){
    ?>
    <div class="grid-x grid-margin-x">
        <div class="cell">
            <?php
            Table::create(array(
                'dataSource' => $this->dataStores['download'],
                "columns" => getTablefieldsDefault($showAvailability,$showAvailabilitySecond,$useGridMeterDayData,$lineBreake,$doubleLineBreake,$gradCelsius,$durchschnitt),
                "cssClass"=>array(
                    "table"=>"table-bordered table-striped table-hover"
                ),
                "max-width"=>"2000px",
                "height"=>"100%",
            ));
            ?>
        </div>
    </div>
    <?php
}
if($params['tableType'] == 'daybase'){
    ?>
    <div class="grid-x grid-margin-x">
        <div class="cell">
            <?php
            Table::create(array(
                'dataSource' => $this->dataStores['download'],
                "columns"=>getTablefieldsDaybase($lineBreake,$doubleLineBreake),
                "cssClass"=>array(
                    "table"=>"table-bordered table-striped table-hover"
                ),
                "max-width"=>"2000px",
                "height"=>"100%",
            ));
            ?>
        </div>
    </div>
    <?php
}


$chart = new ECharts();
$chart->tooltip->show = true;
$chart->visualMap->min = 0;
$chart->visualMap->max = 100;
$chart->visualMap->text = ['High', 'Low'];
$chart->visualMap->calculable = true;
$chart->visualMap->inRange->color = ['#098f09', '#fa506c'];
$chart->tooltip->trigger = 'item';
$chart->tooltip->formatter = '{a}
{b}  {c}';
$series = new Series();
$series->name = 'Times';
$series->type = 'map';
$chart->xAxis[] = array(
    'type' => 'category',
    'axisLabel' => array(
            'show' => true,
            'margin' => '18.5',
    ),
    'splitArea' => array(
        'show' => true,
    ),
    'data' => array('INV 1', 'INV 2', 'INV 3', 'INV 4', 'INV 5', 'INV 6', 'INV 7', 'INV 8')
);
$chart->yAxis[] = array(
    'type' => 'value',
    'inverse' => true,
    'min' => 1,
    'max' => 15,
    'maxInterval' => 1,
    'data' => array('1', '2', '3', '4', '5', '6', '7', '8', '9', '10','11','12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24'),
);
$chart->series[] = array(
    'name' => 'Inverters',
    'type' => 'heatmap',
    'data' => array(
            [0,1,6],[0,2,15],[0,3,40],[0,4,35],[0,5,19],[0,6,22],[0,7,12],[0,8,7],[0,9,15],[0,10,92],[0,11,32],[0,12,14],[0,13,28],[0,14,18],[0,15,10],
            [1,1,12],[1,2,21],[1,3,10],[1,4,75],[1,5,39],[1,6,22],[1,7,12],[1,8,7],[1,9,15],[1,10,72],[1,11,32],[1,12,14],[1,13,28],[1,14,18],[1,15,56],),
    'label' => array(
        'show' => true,
    ),
);

$option = array (
    'title' => array (
        'text' => 'String current to the expected current xxx',
    ),
    'tooltip' =>
        array (
            'show' => true,
        ),
    'legend' =>
        array (
            'data' =>
                array (
                    0 => 'yyyyyy',
                ),
            'show' => true,
            'left' => 'center',
            'bottom' => 10
        ),
    'grid' =>
        array (
            'height' => '40%',
            'top' => '10%',
        ),
    'toolbox' =>
        array (
            'show' => false,
        ),
    'brush' => array(),
    'visualMap' => array (
        'show' => false,
    ),
    // ...
);
Config::addExtraScript('infographic.js', 'https://dev.g4npvplus.net/echarts/theme/');
$chart->setOption($option);

echo $chart->render('heatmapp', ['style' => 'height: 500px;'], 'royal');
?>

<div id="chatter" style="width: 600px;height:600px;"></div>
<?php

if ($params['doctype'] == 'pdf') {
    include_once __DIR__ . '/../views/view_pdf_header_footer.tmpl';
    echo getHeaderFooter($params,$plantPower);
}
?>

<script type="text/javascript">
    // based on prepared DOM, initialize echarts instance
    var secondChart = echarts.init(document.getElementById('chatter'), 'sakura');

    // specify chart configuration item and data
    var option = {
        title: {
            text: 'Detailed analysis',
        },
        grid: {
            left: '3%',
            right: '7%',
            bottom: '7%',
            containLabel: true
        },
        tooltip: {
            // trigger: 'axis',
            showDelay: 0,
            formatter: function (params) {
                if (params.value.length > 1) {
                    return params.seriesName + ' :<br/>'
                        + params.value[0] + '% '
                        + params.value[1] + '% ';
                        + params.value[2] + '% ';
                }
                else {
                    return params.seriesName + ' :<br/>'
                        + params.name + ' : '
                        + params.value + '% ';
                }
            },
            axisPointer: {
                show: true,
                type: 'cross',
                lineStyle: {
                    type: 'dashed',
                    width: 1
                }
            }
        },
        toolbox: {
            show: false
        },
        brush: {
        },
        legend: {
            data: ['actual', 'expected','customized'],
            left: 'center',
            bottom: 10
        },
        xAxis: [
            {
                type: 'value',
                scale: true,
                axisLabel: {
                    formatter: '{value}'
                },
                splitLine: {
                    show: false
                },
                maxInterval: 1
            }
        ],
        yAxis: [
            {
                type: 'value',
                scale: true,
                axisLabel: {
                    formatter: '{value} %'
                },
                splitLine: {
                    show: false
                },
                inverse: false,
                max: 100,
            }
        ],
        series: [
            {
                name: 'actual',
                type: 'scatter',
                emphasis: {
                    focus: 'series'
                },
                data: [[5, -100], [6, -90], [7, -80], [8, -5], [9, -4], [10, -1], [11, 2], [12, 5], [13, 10], [14, 10], [15, 8], [16, 5], [17, -5], [18, -8.0], [19, -53.6], [20, -80.0], [21, -90],

                ],
            },
            {
                name: 'expected',
                type: 'scatter',
                emphasis: {
                    focus: 'series'
                },
                data: [[5, -100], [6, -92], [7, -86], [8, -2], [9, 3.6], [10, 6], [11, 15], [12, 12], [13, 15], [14, 12], [15, 11], [16, 9.0], [17, -7], [18, -6], [19, -80], [20, -95], [21, -100],
                ],
            },
            {
                name: 'customized',
                type: 'scatter',
                emphasis: {
                    focus: 'series'
                },
                data: [[5, -100], [6, -93], [7, -68], [8, 3], [9, 9], [10, 12], [11, 18], [12, 25], [13, 22], [14, 19], [15, 17], [16, 5], [17, -3], [18, -5.0], [19, -83.6], [20, -93.0], [21, -100],
                ],
            },
        ]
    };
    // use configuration item and data specified to show chart
    secondChart.setOption(option);
    var img = new Image();
    img.src = secondChart.getDataURL({
        pixelRatio: 1,
        backgroundColor: '#00f'
    });
    //$("#xxx").attr("src",img.src);

</script>

</body>
</html>