<style>
    .custom-select,
    .filter-template input[type="text"] {
        display: inline-block;
        width: auto;
    }
    .tab-pane {
        border: solid 1px #dee2e6;
        margin-top: -2px;
        min-height: 60px;
        padding: 15px;
    }

    .dataTables_wrapper .dataTables_length {
        padding-top: 0.755em;
        margin-right: 1em;
    }

    .dataTables_wrapper .dataTables_info {
        clear: none;
    }
</style>
<?php
    $resultTabs = [
        [
            'title' => 'Table',
            'text' => '',
            'include' => "DataTable.php",
        ],
        [
            'title' => 'Chart',
            'text' => '',
            'include' => "DataChart.php",
        ],
    ];
?>
<div>
    <div>
        <ul class="nav nav-tabs" role="tablist">
        <?php
            foreach ($resultTabs as $i => $tab) {
                $title = $tab['title'];
                $active = $i === 0 ? 'active' : '';
                echo "<li class='nav-item'>
                    <a class='nav-link $active' id='$name-$title-tab' data-toggle='tab' href='#$name-$title' role='tab' aria-controls='profile' aria-selected='false'>$title</a>
                </li>";
            }
        ?>
        </ul>
    
        <div class="tab-content">
        <?php
            foreach ($resultTabs as $i => $tab) {
                $title = $tab['title'];
                $text = $tab['text'];
                $include = $tab['include'];
                $active = $i === 0 ? 'active' : '';
                echo "<div class='tab-pane $active' id='$name-$title' role='tabpanel' aria-labelledby='$name-$title-tab'>$text <br>";
                include $include;
                echo "</div>";
            }
        ?>
        </div>
    </div>
</div>
<div class='dom-templates' style="display:none">
    <select class='select-template'></select>
    <option class='option-template'></option>
</div>
<script type="text/javascript">
    KoolReport.widget.init(<?php echo json_encode($this->getResources()); ?>, function() {
        <?php $this->clientSideBeforeInit();?>
        <?php echo $this->name; ?>_data = {
            name: '<?php echo $this->name; ?>',
            data: <?php echo json_encode($this->dataStore->data()); ?>,
            meta: <?php echo json_encode($this->dataStore->meta()); ?>,
            columns: <?php echo json_encode($this->columns); ?>,
            options: <?php echo json_encode($this->options); ?>,
        }
        <?php echo $name; ?> = KoolReport.ChartTable.create(<?php echo $name; ?>_data);
        <?php $this->clientSideReady(); ?>
    });
</script>



