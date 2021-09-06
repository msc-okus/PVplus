<?php


namespace App\Reports\Goldbeck;


use koolreport\KoolReport;
use koolreport\processes\Filter;

class EPCMonthlyPRGuaranteeReport extends KoolReport
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
                'header'        => [
                    'class'         => '\koolreport\datasources\ArrayDataSource',
                    'data'          => $this->params['header'],
                ],
                'main'          => [
                    'class'         => '\koolreport\datasources\ArrayDataSource',
                    'data'          => $this->params['main'],
                ],
                'forecast'      => [
                    'class'         => '\koolreport\datasources\ArrayDataSource',
                    'data'          => $this->params['forecast'],
                ],
                'forecast_real' => [
                    'class'         => '\koolreport\datasources\ArrayDataSource',
                    'data'          => $this->params['forecast_real'],
                ],
                'pld'           => [
                    'class'         => '\koolreport\datasources\ArrayDataSource',
                    'data'          => $this->params['pld'],
                ],
                'legend'        => [
                    'class'         => '\koolreport\datasources\ArrayDataSource',
                    'data'          => $this->params['legend'],
                ],
                'formel'        => [
                    'class'         => '\koolreport\datasources\ArrayDataSource',
                    'data'          => $this->params['formel'],
                ],
            ]
        ];
    }

    protected function setup()
    {
        $this->src('headlines')->pipe($this->dataStore('headlines'));
        $this->src('header')->pipe($this->dataStore('header'));
        $this->src('main')->pipe($this->dataStore('main'));
        $this->src('forecast')->pipe($this->dataStore('forecast'));
        $this->src('forecast_real')->pipe($this->dataStore('forecast_real'));
        $this->src('pld')->pipe($this->dataStore('pld'));
        $this->src('legend')->pipe($this->dataStore('legend'));
        $this->src('formel')->pipe($this->dataStore('formel'));
        $this->src('main')
            ->pipe(new Filter([
                ['month', 'notContain', '<br>']
            ]))
            ->pipe($this->dataStore('graph'))
        ;
    }


}