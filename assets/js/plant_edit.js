import $ from 'jquery';

// Module
let $wrapper_module = $('.js-module-wrapper');
$wrapper_module.on('click', '.js-remove-module', function (e) {
    e.preventDefault();
    $(this).closest('.js-module-item')
        .remove();
});
$wrapper_module.on('click', '.js-add-module', function (e) {
    e.preventDefault();
    let prototype = $wrapper_module.data('prototype');
    let index = $wrapper_module.data('index');
    let newForm = prototype.replace(/__name__/g, index);
    $wrapper_module.data('index', index + 1);
    $('#modules>ul').append(newForm);
    Foundation.reInit('accordion');
});

// time Config
let $wrapper_timeconfig = $('.js-timeConfig-wrapper');
$wrapper_timeconfig.on('click', '.js-remove-timeConfig-module', function (e) {
    e.preventDefault();
    $(this).closest('.js-timeConfig-item')
        .remove();
});
$wrapper_timeconfig.on('click', '.js-add-timeConfig', function (e) {
    console.log('test');
    e.preventDefault();
    let prototype = $wrapper_timeconfig.data('prototype');
    let index = $wrapper_timeconfig.data('index');
    let newForm = prototype.replace(/__name__/g, index);
    $wrapper_timeconfig.data('index', index + 1);
    $('#timeConfig>tbody').append(newForm);
});

// Event Mail
let $wrapper_eventmail = $('.js-eventmail-wrapper');
$wrapper_eventmail.on('click', '.js-remove-eventmail', function(e) {
    e.preventDefault();
    $(this).closest('.js-eventmail-item')
        .remove();
});
$wrapper_eventmail.on('click', '.js-add-eventmail', function(e) {
    e.preventDefault();
    let prototype = $wrapper_eventmail.data('prototype');
    let index = $wrapper_eventmail.data('index');
    let newForm = prototype.replace(/__name__/g, index);
    $wrapper_eventmail.data('index', index + 1);
    $('#even-mail>tbody').append(newForm);
});

// legend
let $wrapper_legend = $('.js-legend-monthly-wrapper');
$wrapper_legend.on('click', '.js-remove-legend-monthly', function(e) {
    e.preventDefault();
    $(this).closest('.js-legend-monthly-item')
        .remove();
});
$wrapper_legend.on('click', '.js-add-legend-monthly', function(e) {
    e.preventDefault();
    let prototype = $wrapper_legend.data('prototype');
    let index = $wrapper_legend.data('index');
    let newForm = prototype.replace(/__name__/g, index);
    $wrapper_legend.data('index', index + 1);
    $('#legend-monthly>tbody').append(newForm);
});

// legend EPC
let $wrapper_epc = $('.js-legend-epc-wrapper');
$wrapper_epc.on('click', '.js-remove-legend-epc', function(e) {
    e.preventDefault();
    $(this).closest('.js-legend-epc-item')
        .remove();
});
$wrapper_epc.on('click', '.js-add-legend-epc', function(e) {
    e.preventDefault();
    let prototype = $wrapper_epc.data('prototype');
    let index = $wrapper_epc.data('index');
    let newForm = prototype.replace(/__name__/g, index);
    $wrapper_epc.data('index', index + 1);
    $('#legend-epc>tbody').append(newForm);
});

// pvsyst Design Werte
let $wrapper_pvsyst = $('.js-pvsyst-wrapper');
$wrapper_pvsyst.on('click', '.js-remove-pvsyst', function(e) {
    e.preventDefault();
    $(this).closest('.js-pvsyst-item')
        .remove();
});
$wrapper_pvsyst.on('click', '.js-add-pvsyst', function(e) {
    e.preventDefault();
    let prototype = $wrapper_pvsyst.data('prototype');
    let index = $wrapper_pvsyst.data('index');
    let newForm = prototype.replace(/__name__/g, index);
    $wrapper_pvsyst.data('index', index + 1);
    $('#pvsyst-values>tbody').append(newForm);
});

// monthly-yield
let $wrapper_yield = $('.js-monthly-yield-wrapper');
$wrapper_yield.on('click', '.js-remove-monthly-yield', function(e) {
    e.preventDefault();
    $(this).closest('.js-monthly-yield-item')
        .remove();
});
$wrapper_yield.on('click', '.js-add-monthly-yield', function(e) {
    e.preventDefault();
    let prototype = $wrapper_yield.data('prototype');
    let index = $wrapper_yield.data('index');
    let newForm = prototype.replace(/__name__/g, index);
    $wrapper_yield.data('index', index + 1);
    $('#monthly-yield-values>tbody').append(newForm);
});

// Economics
let $wrapper_economic = $('.js-economicsetValues-wrapper');
$wrapper_economic.on('click', '.js-economic-let-value-add',function(e){
    e.preventDefault();
    let prototype = $wrapper_economic.data('prototype');
    let index = $wrapper_economic.data('index');
    let newForm = prototype.replace(/__name__/g, index);
    $wrapper_economic.data('index', index+1);
    $('#economicsvalues-values>tbody').append(newForm);
})

// Gruppen
let $wrapper_group = $('.js-group-wrapper');
$wrapper_group.on('click', '.js-remove-group', function(e) {
    e.preventDefault();
    $(this).closest('.js-group-item')
        .remove();
});
$wrapper_group.on('click', '.js-add-group', function(e) {
    e.preventDefault();
    let prototype = $wrapper_group.data('prototype');
    let index = $wrapper_group.data('index');
    let newForm = prototype.replace(/__name__/g, index);
    $wrapper_group.data('index', index + 1);
    $('#group>ul').append(newForm);
    Foundation.reInit('accordion');
});

// Gruppen - Module
let $wrapper_use_module = $('.js-use-module-wrapper');
$wrapper_use_module.on('click', '.js-remove-use-module', function(e) {
    e.preventDefault();
    $(this).closest('.js-use-module-item')
        .remove();
});
$wrapper_use_module.on('click', '.js-add-use-module', function(e) {
    console.log('Yes');
    e.preventDefault();
    let prototype = $wrapper_use_module.data('prototype');
    let index = $wrapper_use_module.data('index');
    let groupId = e.currentTarget.dataset.groupid;
    let newForm = prototype.replace(/__name__/g, index).replace(/_groups_0/g, '_groups_'+(groupId-1)).replace(/\[groups\]\[0\]/g, '\[groups\]\['+(groupId-1)+'\]');
    $wrapper_use_module.data('index', index + 1);
    $("#use-modules-"+groupId+">tbody").append(newForm);
});


// Gruppen - Monate
let $wrapper_month = $('.js-month-wrapper');
$wrapper_month.on('click', '.js-remove-month', function(e) {
    e.preventDefault();
    $(this).closest('.js-month-item')
        .remove();
});
$wrapper_month.on('click', '.js-add-month', function(e) {
    e.preventDefault();
    let prototype = $wrapper_month.data('prototype');
    let index = $wrapper_month.data('index');
    let groupId = e.currentTarget.dataset.groupid;
    let newForm = prototype.replace(/__name__/g, index).replace(/_groups_0/g, '_groups_'+(groupId-1)).replace(/\[groups\]\[0\]/g, '\[groups\]\['+(groupId-1)+'\]');
    $wrapper_month.data('index', index + 1);
    $('#months-'+groupId+'>tbody').append(newForm);
});

// Anlagen - Monate
let $wrapper_plant_month = $('.js-plant-month-wrapper');
$wrapper_plant_month.on('click', '.js-remove-plant-month', function(e) {
    e.preventDefault();
    $(this).closest('.js-plant-month-item')
        .remove();
});
$wrapper_plant_month.on('click', '.js-add-plant-month', function(e) {
    e.preventDefault();
    let prototype = $wrapper_plant_month.data('prototype');
    let index = $wrapper_plant_month.data('index');
    let newForm = prototype.replace(/__name__/g, index);
    $wrapper_plant_month.data('index', index + 1);
    $('#plant-months>tbody').append(newForm);
});

// AC Gruppe
let $wrapper_acgroup = $('.js-acgroup-wrapper');
$wrapper_acgroup.on('click', '.js-remove-acgroup', function(e) {
    e.preventDefault();
    $(this).closest('.js-acgroup-item')
        .remove();
});
$wrapper_acgroup.on('click', '.js-add-acgroup', function(e) {
    e.preventDefault();
    let prototype = $wrapper_acgroup.data('prototype');
    let index = $wrapper_acgroup.data('index');
    let newForm = prototype.replace(/__name__/g, index);
    $wrapper_acgroup.data('index', index + 1);
    $('#js-acgroup>tbody').append(newForm);
    Foundation.reInit('accordion');
});