import $ from 'jquery';

// Sensors
let $wrapper_sensors = $('.js-sensors-wrapper');
$wrapper_sensors.on('click', '.js-remove-sensor', function (e) {
    e.preventDefault();
    $(this).closest('.js-sensor-item')
        .remove();
});
$wrapper_sensors.on('click', '.js-add-sensor', function (e) {
    e.preventDefault();
    let prototype = $wrapper_sensors.data('prototype');
    let index = $wrapper_sensors.data('index');
    let newForm = prototype.replace(/__name__/g, index);
    $wrapper_sensors.data('index', index + 1);
    $('#js-sensors>tbody').append(newForm);
    Foundation.reInit('accordion');
});

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
/*
* MS  08/2023
* jquery delete sunshading data from fields
*/
// SunShading Wrapper
let $wrapper_sunshading = $('.js-sunshading-wrapper');
$wrapper_sunshading.on('click', '.js-remove-sunshading', function (e) {
    e.preventDefault();
    Swal.fire({
        title: "Are you sure?",
        text: "You want to delete a sunshading Model!",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#126195",
        timer: 80000,
        confirmButtonText: "Yes, do it!",
        cancelButtonText: "No, cancel it!",
        showCloseButton: true,
        allowOutsideClick: false,
        allowEscapeKey: false,
        focusConfirm: true
    }).then((result) => {
        if (result.isConfirmed) {
           $(this).closest('.js-sunshading-item').remove();
        }
    });

});
// SunShading Add
$('.js-add-sunshading').on('click',   function(e) {
    e.preventDefault();
    let prototype = $wrapper_sunshading.data('prototype');
    let index = $wrapper_sunshading.data('index');
    let newForm = prototype.replace(/__name__/g, index);
    $wrapper_sunshading.data('index', index + 1);
    $('#sunshading>ul').append(newForm);
    Foundation.reInit('accordion');
} );
/*
* MS  08/2023
* jquery copy sunshading data into new input fields
*/
// SunShading Copy
$('.js-copy-sunshading').click(function() {
    // copy data from wrapper_sunshading
    let prototype = $wrapper_sunshading.data('prototype');
    let indexrow = $wrapper_sunshading.data('index') -1;
    // predefine the id with [indexrow] from to copy value
    var cpfield0 ='#anlage_form_anlageSunShading_'+indexrow+'_description';
    var cpfield1 ='#anlage_form_anlageSunShading_'+indexrow+'_mod_tilt';
    var cpfield2 ='#anlage_form_anlageSunShading_'+indexrow+'_mod_height';
    var cpfield3 ='#anlage_form_anlageSunShading_'+indexrow+'_mod_width';
    var cpfield4 ='#anlage_form_anlageSunShading_'+indexrow+'_mod_table_height';
    var cpfield5 ='#anlage_form_anlageSunShading_'+indexrow+'_mod_table_distance';
    var cpfield6 ='#anlage_form_anlageSunShading_'+indexrow+'_distance_a';
    var cpfield7 ='#anlage_form_anlageSunShading_'+indexrow+'_distance_b';
    var cpfield8 ='#anlage_form_anlageSunShading_'+indexrow+'_ground_slope';
    var cpfield9 ='#anlage_form_anlageSunShading_'+indexrow+'_modulesDB';
    var cpfield10 ='#anlage_form_anlageSunShading_'+indexrow+'_has_row_shading';
    var cpfield11 ='#anlage_form_anlageSunShading_'+indexrow+'_mod_alignment';
    var cpfield12 ='#anlage_form_anlageSunShading_'+indexrow+'_mod_long_page';
    var cpfield13 ='#anlage_form_anlageSunShading_'+indexrow+'_mod_short_page';
    var cpfield14 ='#anlage_form_anlageSunShading_'+indexrow+'_mod_row_tables';
    // build the new wrapper
    let index = $wrapper_sunshading.data('index');
    let newForm = prototype.replace(/__name__/g, index);
    $wrapper_sunshading.data('index', index + 1);
    $('#sunshading>ul').append(newForm);
    indexrow = indexrow + 1;
    // predefine the insert id [indexrow] of value
    var nwfield0 ='#anlage_form_anlageSunShading_'+indexrow+'_description';
    var nwfield1 ='#anlage_form_anlageSunShading_'+indexrow+'_mod_tilt';
    var nwfield2 ='#anlage_form_anlageSunShading_'+indexrow+'_mod_height';
    var nwfield3 ='#anlage_form_anlageSunShading_'+indexrow+'_mod_width';
    var nwfield4 ='#anlage_form_anlageSunShading_'+indexrow+'_mod_table_height';
    var nwfield5 ='#anlage_form_anlageSunShading_'+indexrow+'_mod_table_distance';
    var nwfield6 ='#anlage_form_anlageSunShading_'+indexrow+'_distance_a';
    var nwfield7 ='#anlage_form_anlageSunShading_'+indexrow+'_distance_b';
    var nwfield8 ='#anlage_form_anlageSunShading_'+indexrow+'_ground_slope';
    var nwfield9 ='#anlage_form_anlageSunShading_'+indexrow+'_modulesDB';
    var nwfield10 ='#anlage_form_anlageSunShading_'+indexrow+'_has_row_shading';
    var nwfield11 ='#anlage_form_anlageSunShading_'+indexrow+'_mod_alignment';
    var nwfield12 ='#anlage_form_anlageSunShading_'+indexrow+'_mod_long_page';
    var nwfield13 ='#anlage_form_anlageSunShading_'+indexrow+'_mod_short_page';
    var nwfield14 ='#anlage_form_anlageSunShading_'+indexrow+'_mod_row_tables';
    // begin copy
    $(nwfield0).val($(cpfield0).val());
    $(nwfield1).val($(cpfield1).val());
    $(nwfield2).val($(cpfield2).val());
    $(nwfield3).val($(cpfield3).val());
    $(nwfield4).val($(cpfield4).val());
    $(nwfield5).val($(cpfield5).val());
    $(nwfield6).val($(cpfield6).val());
    $(nwfield7).val($(cpfield7).val());
    $(nwfield8).val($(cpfield8).val());
    $(nwfield9).val($(cpfield9).val());
    $(nwfield10).val($(cpfield10).val());
    $(nwfield11).val($(cpfield11).val());
    $(nwfield12).val($(cpfield12).val());
    $(nwfield13).val($(cpfield13).val());
    $(nwfield14).val($(cpfield14).val());
    // ende copy and reinitzial accordion
    $('#accordion-title').text('NEW Sun Shading Model from a COPY:');
    Foundation.reInit('accordion');
} );

// the Time Config wrapper
let $wrapper_timeconfig = $('.js-timeConfig-wrapper');
$wrapper_timeconfig.on('click', '.js-remove-timeConfig-module', function (e) {
    e.preventDefault();
    $(this).closest('.js-timeConfig-item')
        .remove();
});
$wrapper_timeconfig.on('click', '.js-add-timeConfig', function (e) {
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
let $wrapper_legend = $('.js-legend-_monthly-wrapper');
$wrapper_legend.on('click', '.js-remove-legend-_monthly', function(e) {
    e.preventDefault();
    $(this).closest('.js-legend-_monthly-item')
        .remove();
});
$wrapper_legend.on('click', '.js-add-legend-_monthly', function(e) {
    e.preventDefault();
    let prototype = $wrapper_legend.data('prototype');
    let index = $wrapper_legend.data('index');
    let newForm = prototype.replace(/__name__/g, index);
    $wrapper_legend.data('index', index + 1);
    $('#legend-_monthly>tbody').append(newForm);
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
let $wrapper_economic = $('.js-economicVarValues-wrapper');
$wrapper_economic.on('click', '.js-economic-var-value-add',function(e){
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
});

// PPCs
let $wrapper_ppcs = $('.js-ppcs-wrapper');
$wrapper_ppcs.on('click', '.js-remove-ppc', function(e) {
    e.preventDefault();
    $(this).closest('.js-ppc-item')
        .remove();
});
$wrapper_ppcs.on('click', '.js-add-ppc', function(e) {
    e.preventDefault();
    let prototype = $wrapper_ppcs.data('prototype');
    let index = $wrapper_ppcs.data('index');
    let newForm = prototype.replace(/__name__/g, index);
    $wrapper_ppcs.data('index', index + 1);
    $('#js-ppcs>tbody').append(newForm);
});