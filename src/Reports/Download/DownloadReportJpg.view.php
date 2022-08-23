<?php
\koolreport\widgets\google\ComboChart::create([
    'title' => $this->params['datachart'][repportHeadline],
    'dataSource' => $this->params['datachart'][datachartoutput],
    'columns' => [
        'Time',
        'gridac' => [
            'label' => 'gridac',
            'type' => 'number',
        ],
        'invacv' => [
            'label' => 'invacv',
            'type' => 'number',
        ],
        'invdcv' => [
            'label' => 'invdcv',
            'type' => 'number',
        ],
        'irradiation' => [
            'label' => 'irradiation',
            'type' => 'number',
            'chartType' => 'line',
        ],
    ],
    'options' => [
        'series' => [
            0 => ['targetAxisIndex' => 0],
            3 => ['targetAxisIndex' => 1],
        ],
        'vAxes' => [
            0 => ['title' => 'KWH'],
            1 => ['title' => 'Irradiation'],
        ],
    ],
    'max-width' => '900px',
    'width' => '900px',
]);
?>

<?php
\koolreport\widgets\google\Table::create([
    'dataStore' => $this->dataStore('reports'),
    'columns' => [
        $this->params['data'][0][0][0] => [
            'type' => 'datetime',
        ],
        $this->params['data'][0][0][1] => [
            'type' => 'number',
        ],
        $this->params['data'][0][0][2] => [
            'type' => 'number',
        ],
        $this->params['data'][0][0][3] => [
            'type' => 'number',
        ],
        $this->params['data'][0][0][4] => [
            'type' => 'number',
        ],
        $this->params['data'][0][0][5] => [
            'type' => 'number',
        ],
        $this->params['data'][0][0][6] => [
            'type' => 'number',
        ],
        $this->params['data'][0][0][7] => [
            'type' => 'number',
        ],
        $this->params['data'][0][0][8] => [
            'type' => 'number',
        ],
        $this->params['data'][0][0][9] => [
            'type' => 'number',
        ],
        $this->params['data'][0][0][10] => [
            'type' => 'number',
        ],
        $this->params['data'][0][0][11] => [
            'type' => 'number',
        ],
    ],
    'cssClass' => [
        'table' => 'table table-hover table-bordered',
    ],
    'width' => '100%',
    'height' => '100%',
]);
