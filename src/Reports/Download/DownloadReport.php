<?php

namespace App\Reports\Download;
use \koolreport\processes\Group;

use \koolreport\KoolReport;
use \koolreport\processes\Filter;
use \koolreport\processes\TimeBucket;
use \koolreport\processes\Limit;
use \koolreport\processes\ColumnMeta;
use \koolreport\processes\Sort;
use \koolreport\processes\RemoveColumn;
use \koolreport\processes\OnlyColumn;
use \koolreport\processes\Map;
use \koolreport\cube\processes\Cube;
use \koolreport\core\Utility as Util;



class DownloadReport extends \koolreport\KoolReport
{
    use \koolreport\cloudexport\Exportable;
    use \koolreport\excel\BigSpreadsheetExportable;

    public function settings()
    {
        return array(
            "dataSources"=>array(
                'download'    => [
                    'class'         => '\koolreport\datasources\ArrayDataSource',
                    'data'          => $this->params['download'],
                ],
                'params'    => [
                    'class'         => '\koolreport\datasources\ArrayDataSource',
                    'data'          => $this->params['params'],
                ],
            )
        );
    }
    protected function setup()
    {
        $this->src('download')->pipe($this->dataStore('download'));
        $this->src('params')->pipe($this->dataStore('params'));

    }
}