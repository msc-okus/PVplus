<?php

namespace App\Reports\Download;

class DownloadReport extends \koolreport\KoolReport
{
    use \koolreport\cloudexport\Exportable;

    use \koolreport\excel\BigSpreadsheetExportable;

    public function settings(): array
    {
        return [
            'dataSources' => [
                'download' => [
                    'class' => '\koolreport\datasources\ArrayDataSource',
                    'data' => $this->params['download'],
                ],
                'params' => [
                    'class' => '\koolreport\datasources\ArrayDataSource',
                    'data' => $this->params['params'],
                ],
            ],
        ];
    }

    protected function setup()
    {
        $this->src('download')->pipe($this->dataStore('download'));
        $this->src('params')->pipe($this->dataStore('params'));
    }
}
