<?php

use \koolreport\excel\Table;

$params = $this->dataStores['params']->toArray()[0];

$showAvailability = $params['showAvailability'];
$showAvailabilitySecond = $params['showAvailabilitySecond'];
$useGridMeterDayData = $params['useGridMeterDayData'];
$gradCelsius = '°';
$durchschnitt = 'Ø';

$sheet1 = "Data";

include_once __DIR__ . '/table_fields_downloads.tmpl';
?>
<meta charset="UTF-8">
<meta name="keywords" content="">
<meta name="creator" content="green4net">
<meta name="subject" content="">
<meta name="title" content="">

<div sheet-name="<?php echo $sheet1; ?>">
    <div><?php echo $params['downloadHeadline'].' for '.$params['downloadPlantName'];?></div>
    <div>

        <?php

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
                        "columns"=>getTablefieldsDaybase(),
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
        ?>
        <div class="grid-x grid-margin-x">
            <div class="cell">
                xxxxx<img id="xxx" src="">
            </div>
        </div>

    </div>

</div>

<script src="/echarts/echarts.min.js" type="text/javascript"></script>
<script src="/echarts/theme/royal.js" type="text/javascript"></script>
<script src="/echarts/theme/sakura.js" type="text/javascript"></script>
<script type="text/javascript">
    // based on prepared DOM, initialize echarts instance
    var myChart = echarts.init(document.getElementById('gurke'), 'royal');
    var days = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10','11','12',
        '13', '14', '15', '16', '17', '18',
        '19', '20', '21', '22', '23', '24'];
    var inverters = ['INV 1', 'INV 2', 'INV 3', 'INV 4', 'INV 5', 'INV 6', 'INV 7', 'INV 8'];

    var data = [[1,0,6],[2,0,15],[3,0,40],[4,0,35],[5,0,19],[6,0,22],[7,0,12],[8,0,7],[9,0,15],[10,0,72],[11,0,32],[12,0,14],[13,0,28],[14,0,18],[15,0,10],
        [1,1,12],[2,1,21],[3,1,10],[4,1,75],[5,1,39],[6,1,22],[7,1,12],[8,1,7],[9,1,15],[10,1,72],[11,1,32],[12,1,14],[13,1,28],[14,1,18],[15,1,56],
        [1,2,2],[2,2,79],[3,2,80],[4,2,25],[5,2,69],[6,2,22],[7,2,12],[8,2,7],[9,2,15],[10,2,72],[11,2,32],[12,2,14],[13,2,28],[14,2,18],[15,2,22],
        [1,3,19],[2,3,33],[3,3,90],[4,3,45],[5,3,29],[6,3,22],[7,3,12],[8,3,7],[9,3,15],[10,3,72],[11,3,32],[12,3,14],[13,3,28],[14,3,18],[15,3,3],
        [1,4,22],[2,4,24],[3,4,10],[4,4,5],[5,4,89],[6,4,22],[7,4,12],[8,4,7],[9,4,15],[10,4,72],[11,4,32],[12,4,14],[13,4,28],[14,4,18],[15,4,87],
    ];

    data = data.map(function (item) {
        return [item[1], item[0], item[2] || '-'];
    });
    // specify chart configuration item and data
    var option = {
        title: {
            text: 'String current to the expected current',
        },
        tooltip: {
            position: 'top'
        },
        grid: {
            height: '40%',
            height: '40%',
            top: '10%'
        },
        toolbox: {
            show: false
        },
        xAxis: {
            type: 'category',
            data: inverters,
            splitArea: {
                show: true
            },
            axisLabel: {
                show: true,
                margin: 18.5
            }
        },
        yAxis: {
            type: 'value',
            data: days,
            splitArea: {
                show: false
            },
            inverse: true,
            min: 1,
            max: 15,
            maxInterval: 1
        },
        visualMap: {
            show: false
        },
        series: [{
            type: 'heatmap',
            data: data,
            label: {
                show: true
            },
            emphasis: {
                itemStyle: {
                    shadowBlur: 10,
                    shadowColor: 'rgba(0, 0, 0, 0.5)'
                }
            }
        }]
    };
    // use configuration item and data specified to show chart
    myChart.setOption(option);
    var img = new Image();
    img.src = myChart.getDataURL({
        pixelRatio: 1,
        backgroundColor: '#00f'
    });
    $("#xxx").attr("src",img.src);
</script>