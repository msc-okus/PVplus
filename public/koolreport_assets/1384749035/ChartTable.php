<?php

namespace koolreport\charttable;

use \koolreport\core\Utility as Util;

class ChartTable extends \koolreport\core\Widget
{
    public function version()
    {
        return "1.0.0";
    }

    protected function resourceSettings()
    {
        $themeBase = $this->getThemeBase();
        if (empty($themeBase)) $themeBase = 'bs3';

        $jsResources1st = $jsResources2nd = $jsResources3rd = $cssResources = [];

        $dtPath = "../datagrid/DataTables";
        switch ($themeBase) {
            case "bs4":
                $jsResources1st[] = "$dtPath/datatables.min.js";
                $jsResources2nd[] = "$dtPath/pagination/input.js";
                $jsResources2nd[] = "$dtPath/datatables.bs4.min.js";
                $cssResources[] = "$dtPath/datatables.bs4.min.css";
                break;
            case "bs3":
            default:
                $jsResources1st[] = "$dtPath/datatables.min.js";
                $jsResources2nd[] = "$dtPath/pagination/input.js";
                $cssResources[] = "$dtPath/datatables.min.css";
        }
        $pluginNameToFiles = array(
            "AutoFill" => array(
                "AutoFill-2.3.5/js/dataTables.autoFill.min.js"
            ),
            "Buttons" => array(
                "Buttons-1.6.2/js/dataTables.buttons.min.js",
                "Buttons-1.6.2/js/buttons.colVis.min.js",
                "Buttons-1.6.2/js/buttons.html5.min.js",
                "Buttons-1.6.2/js/buttons.print.min.js",
                "JSZip-2.5.0/jszip.min.js",
                "pdfmake-0.1.36/pdfmake.min.js",
                // ["pdfmake-0.1.36/vfs_fonts.js"], //vfs_fonts must be loaded after pdfmake.min.js
            ),
            "ColReorder" => array(
                "ColReorder-1.5.2/js/dataTables.colReorder.min.js",
            ),
            "FixedColumns" => array(
                "FixedColumns-3.3.1/js/dataTables.fixedColumns.min.js",
            ),
            "FixedHeader" => array(
                "FixedHeader-3.1.7/js/dataTables.fixedHeader.min.js"
            ),
            "KeyTable" => array(
                "KeyTable-2.5.2/js/dataTables.keyTable.min.js"
            ),
            "Responsive" => array(
                "Responsive-2.2.4/js/dataTables.responsive.min.js"
            ),
            "RowGroup" => array(
                "RowGroup-1.1.2/js/dataTables.rowGroup.min.js"
            ),
            "RowReorder" => array(
                "RowReorder-1.2.7/js/dataTables.rowReorder.min.js"
            ),
            "Scroller" => array(
                "Scroller-2.0.2/js/dataTables.scroller.min.js"
            ),
            "SearchPanes" => array(
                "SearchPanes-1.1.0/js/dataTables.searchPanes.min.js"
            ),
            "Select" => array(
                "Select-1.3.1/js/dataTables.select.min.js"
            ),
        );
        $pluginJs = [];
        foreach ($this->plugins as $name) {
            if ($name === "Buttons")
                $jsResources3rd[] = "$dtPath/pdfmake-0.1.36/vfs_fonts.js";
            if (isset($pluginNameToFiles[$name])) {
                foreach ($pluginNameToFiles[$name] as $jsFile) {
                    if (is_string($jsFile)) $jsFile = "$dtPath/$jsFile";
                    else if (is_array($jsFile)) {
                        foreach ($jsFile as $k => $subFile)
                            $jsFile[$k] = "$dtPath/{$jsFile[$k]}";
                    }
                    array_push($pluginJs, $jsFile);
                }
            }
        }
        $jsResources2nd = array_merge($jsResources2nd, $pluginJs);

        $chartjsPath = "../chartjs/clients";
        $jsResources1st[] = "$chartjsPath/Chart.bundle.min.js";
        $jsResources1st[] = "$chartjsPath/chartjs.js";
        $jsResources2nd[] = "$chartjsPath/chartjs-plugin-colorschemes.min.js";

        $inputsPath = "../inputs/bower_components";
        $bootstrapMultiselectDir = $themeBase === 'bs3' ? 
            'bootstrap-multiselect' : 'bootstrap-multiselect-0.9';
        $jsResources1st[] = "$inputsPath/$bootstrapMultiselectDir/bootstrap-multiselect.js";
        $cssResources[] = "$inputsPath/$bootstrapMultiselectDir/bootstrap-multiselect.css";
        
        $jsResources1st[] = "js/charttable.js";

        $jsResources2nd[] = $jsResources3rd;
        $jsResources1st[] = $jsResources2nd;
        $jsResources[] = $jsResources1st;
        $resources = [
            "library" => array("jQuery"),
            "folder" => "",
            "js" => $jsResources,
            "css" => $cssResources,
        ];
        if ($themeBase === 'bs4') {
            // $cssResources[] = "$inputsPath/$bootstrapMultiselectDir/additional.bs4.css";
            $resources['library'][] = 'font-awesome';
        }
        // echo "themeBase=$themeBase<br>";
        // echo "resources="; Util::prettyPrint($resources); exit;

        return $resources;
    }

    protected function onInit()
    {
        $this->useLanguage();

        $this->name = Util::get($this->params, 'id', null);
        $this->name = Util::get($this->params, 'name', $this->name);
        $this->useAutoName("charttable_");

        $scope = Util::get($this->params, "scope", array());
        $this->scope = is_callable($scope) ? $scope() : $scope;
        $this->useDataSource($this->scope);

        $this->columns = Util::get($this->params, 'columns');
        $this->options = Util::get($this->params, 'options');
        $this->onBeforeInit = Util::get($this->params, "onBeforeInit");
        $this->defaultPlugins = Util::get($this->params, "defaultPlugins", [
            "ColReorder", "Select", "Buttons"
        ]);
        $this->plugins = Util::get($this->params, "plugins", []);
        $this->plugins = array_merge($this->plugins, $this->defaultPlugins);
    }

    protected function onRender()
    {
        $this->template("ChartTable", [
            "name" => $this->name
        ]);
    }

    /**
     * Render javascript code to implement user's custom script 
     * just before init DataTables
     * 
     * @return null
     */
    protected function clientSideBeforeInit()
    {
        if ($this->onBeforeInit != null) {
            echo "(" . $this->onBeforeInit . ")();";
        }
    }
}
