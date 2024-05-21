<style>
    krwidget .tab-chart-row {
        margin: 5px 0;
    }
</style>

<div class='form container'>
    <div class='row tab-chart-row'>
        <div class="col-sm-3"><label>Label column: </label></div>
        <div class="col-sm-3"><select class="select-label-column custom-select">
        </select> </div>
    </div>
    <div class='row tab-chart-row'>
        <div class="col-sm-3"><label>Data columns: </label></div>
        <div class="col-sm-3"><select class="select-data-columns custom-select" multiple>
            </select></div>
    </div>
    <div class='row tab-chart-row'>
        <button type='button' class='btn btn-light' onclick='<?php echo $name; ?>.generateChart()'>
        Generate chart</button>
        <button type='button' class='btn btn-light' onclick='<?php echo $name; ?>.generateChart("selected")'>
        Generate chart on selected rows</button>
    </div>
</div>
<canvas id="<?php echo $name; ?>-data-chart"></canvas>