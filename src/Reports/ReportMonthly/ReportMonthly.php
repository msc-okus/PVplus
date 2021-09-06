<?php

namespace App\Reports\ReportMonthly;

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

class ReportMonthly extends \koolreport\KoolReport
{
    use \koolreport\cloudexport\Exportable;
    use \koolreport\excel\BigSpreadsheetExportable;

    public function settings()
    {
        return array(
            "dataSources"=>array(
                'ownparams'    => [
                    'class'         => '\koolreport\datasources\ArrayDataSource',
                    'data'          => $this->params['ownparams'],
                ],
                'headline'    => [
                    'class'         => '\koolreport\datasources\ArrayDataSource',
                    'data'          => $this->params['headline'],
                    'dataFormat'    => 'table',
                ],
                'energyproduction'    => [
                    'class'         => '\koolreport\datasources\ArrayDataSource',
                    'data'          => $this->params['energyproduction'],
                ],
                'performanceratioandavailability'      => [
                    'class'         => '\koolreport\datasources\ArrayDataSource',
                    'data'          => $this->params['performanceratioandavailability'],
                ],
                'dayvalues'      => [
                    'class'         => '\koolreport\datasources\ArrayDataSource',
                    'data'          => $this->params['dayvalues'],
                ],
                'irradiationandtempvalues'      => [
                    'class'         => '\koolreport\datasources\ArrayDataSource',
                    'data'          => $this->params['irradiationandtempvalues'],
                ],
                'daychartvalues'      => [
                    'class'         => '\koolreport\datasources\ArrayDataSource',
                    'data'          => $this->params['daychartvalues'],
                ],
                'case5'      => [
                    'class'         => '\koolreport\datasources\ArrayDataSource',
                    'data'          => $this->params['case5'],
                ],
                'legend'   => [
                    'class'         => '\koolreport\datasources\ArrayDataSource',
                    'data'          => $this->params['legend'],
                ]
            )
        );
    }
    protected function setup()
    {
        $this->src('ownparams')->pipe($this->dataStore('ownparams'));
        $this->src('headline')->pipe($this->dataStore('headline'));
        $this->src('energyproduction')->pipe($this->dataStore('energyproduction'));
        $this->src('performanceratioandavailability')->pipe($this->dataStore('performanceratioandavailability'));
        $this->src('dayvalues')->pipe($this->dataStore('dayvalues'));
        $this->src('irradiationandtempvalues')->pipe($this->dataStore('irradiationandtempvalues'));
        $this->src('daychartvalues')
            ->pipe(new Filter([
                ['datum', 'notContain', 'Date']
            ]))
            ->pipe($this->dataStore('daychartvalues'))
        ;
        $this->src('case5')->pipe($this->dataStore('case5'));
        $this->src('legend')->pipe($this->dataStore('legend'));
    }
}