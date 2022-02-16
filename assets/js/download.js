import $ from 'jquery';

$(document).ready(function () {
    const $month = $("#download_analyse_form_months");
    const $year  = $("#download_analyse_form_years")
    const $days  = $("#download_analyse_form_days");
    var valYears = $year.val();
    var valMonths = $month.val();

    $month.prop( "disabled", true );
    $days.prop( "disabled", true );

    if (valYears == "") {
        $month.prop( "disabled", true );
        $month.val($("#target option:first").val());
        $days.prop( "disabled", true );
        $days.val($("#target option:first").val());
    }
    if (valYears != "") {
        $month.prop( "disabled", false );
    }
    if (valYears != "" &&  valMonths != "") {
        $days.prop( "disabled", false );
    }

    $year.change(function () {
        var val = $(this).val();
        if (val == "") {
            $month.prop( "disabled", true );
            $month.val($("#target option:first").val());
            $days.prop( "disabled", true );
            $days.val($("#target option:first").val());
        }
        if (val != "") {
            $month.prop( "disabled", false );
        }
    });

    $month.change(function () {
        var val = $(this).val();
        if (val == "") {
            $days.prop( "disabled", true );
            $days.val($("#target option:first").val());
        }
        if (val != "") {
            $days.prop( "disabled", false );
        }
    });
});