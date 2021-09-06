<?php
use \koolreport\widgets\koolphp\Table;
use \koolreport\widgets\google\ComboChart;
\koolreport\widgets\google\ComboChart::create(array(
    "title"=>$this->params["datachart"][repportHeadline],
    "dataSource"=>$this->params["datachart"][datachartoutput],
    "columns"=>array(
        "Time",
        "gridac"=>array(
            "label"=>"gridac",
            "type"=>"number"
        ),
        "invacv"=>array(
            "label"=>"invacv",
            "type"=>"number"
        ),
        "invdcv"=>array(
            "label"=>"invdcv",
            "type"=>"number"
        ),
        "irradiation"=>array(
            "label"=>"irradiation",
            "type"=>"number",
            "chartType"=>"line"
        ),
    ),
    "options"=>array(
        "series"=> array(
            0=> array("targetAxisIndex"=> 0),
            3=> array("targetAxisIndex"=> 1),
        ),
        "vAxes"=>array(
            0=> array("title"=> 'KWH'),
            1=> array("title"=> 'Irradiation')
        ),
    ),
    "max-width"=>"900px",
    "width"=>"900px",

));
?>

<?php
\koolreport\widgets\google\Table::create(array(
    "dataStore"=>$this->dataStore('reports'),
    "columns"=>array(
        $this->params["data"][0][0][0]=>array(
            "type"=>"datetime",
        ),
        $this->params["data"][0][0][1]=>array(
            "type"=>"number",
        ),
        $this->params["data"][0][0][2]=>array(
            "type"=>"number",
        ),
        $this->params["data"][0][0][3]=>array(
            "type"=>"number",
        ),
        $this->params["data"][0][0][4]=>array(
            "type"=>"number",
        ),
        $this->params["data"][0][0][5]=>array(
            "type"=>"number",
        ),
        $this->params["data"][0][0][6]=>array(
            "type"=>"number",
        ),
        $this->params["data"][0][0][7]=>array(
            "type"=>"number",
        ),
        $this->params["data"][0][0][8]=>array(
            "type"=>"number",
        ),
        $this->params["data"][0][0][9]=>array(
            "type"=>"number",
        ),
        $this->params["data"][0][0][10]=>array(
            "type"=>"number",
        ),
        $this->params["data"][0][0][11]=>array(
            "type"=>"number",
        )
    ),
    "cssClass"=>array(
        "table"=>"table table-hover table-bordered"
    ),
    "width"=>"100%",
    "height"=>"100%",
));





