<?php


namespace App\Reports\Goldbeck;


use koolreport\KoolReport;
use koolreport\processes\Filter;
use koolreport\processes\Group;
use koolreport\processes\Sort;
use koolreport\processes\Limit;
use koolreport\excel\ExcelExportable;

class EPCMonthlyYieldGuaranteeReport extends KoolReport
{
    use \koolreport\excel\BigSpreadsheetExportable;
    use \koolreport\cloudexport\Exportable;

    protected function settings():array
    {
        return [
            'dataSources'=>[
                'headlines'    => [
                    'class'         => '\koolreport\datasources\ArrayDataSource',
                    'data'          => $this->params['headlines'],
                ],
                'header'    => [
                    'class'         => '\koolreport\datasources\ArrayDataSource',
                    'data'          => $this->params['header'],
                ],
                'main'      => [
                    'class'         => '\koolreport\datasources\ArrayDataSource',
                    'data'          => $this->params['main'],
                ],
                'forecast24'      => [
                    'class'         => '\koolreport\datasources\ArrayDataSource',
                    'data'          => $this->params['forecast24'],
                ],
                'forecast_real'      => [
                    'class'         => '\koolreport\datasources\ArrayDataSource',
                    'data'          => $this->params['forecast_real'],
                ],
                'pld'      => [
                    'class'         => '\koolreport\datasources\ArrayDataSource',
                    'data'          => $this->params['pld'],
                ],
                'legend'   => [
                    'class'         => '\koolreport\datasources\ArrayDataSource',
                    'data'          => $this->params['legend'],
                ]
            ]
        ];
    }

    protected function setup()
    {
        $this->src('headlines')->pipe($this->dataStore('headlines'));
        $this->src('header')->pipe($this->dataStore('header'));
        $this->src('main')->pipe($this->dataStore('main'));
        $this->src('main')
            ->pipe(new Filter([
                ['month', 'notContain', '<br>']
            ]))
            ->pipe($this->dataStore('graph'))
        ;
        $this->src('forecast24')->pipe($this->dataStore('forecast24'));
        $this->src('forecast_real')->pipe($this->dataStore('forecast_real'));
        $this->src('pld')->pipe($this->dataStore('pld'));
        $this->src('legend')->pipe($this->dataStore('legend'));
    }


}