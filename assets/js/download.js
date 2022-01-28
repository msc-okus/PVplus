import $ from 'jquery';

$(document).ready(function () {
    var valYears = $("#download_analyse_form_years").val();
    var valMonths = $("#download_analyse_form_months").val();

    $("#download_analyse_form_months").prop( "disabled", true );
    $("#download_analyse_form_days").prop( "disabled", true );

    if (valYears == "") {
        $("#download_analyse_form_months").prop( "disabled", true );
        $("#download_analyse_form_months").val($("#target option:first").val());
        $("#download_analyse_form_days").prop( "disabled", true );
        $("#download_analyse_form_days").val($("#target option:first").val());
    }
    if (valYears != "") {
        $("#download_analyse_form_months").prop( "disabled", false );
    }
    if (valYears != "" &&  valMonths != "") {
        $("#download_analyse_form_days").prop( "disabled", false );
    }

    $("#download_analyse_form_years").change(function () {
        var val = $(this).val();
        if (val == "") {
            $("#download_analyse_form_months").prop( "disabled", true );
            $("#download_analyse_form_months").val($("#target option:first").val());
            $("#download_analyse_form_days").prop( "disabled", true );
            $("#download_analyse_form_days").val($("#target option:first").val());
        }
        if (val != "") {
            $("#download_analyse_form_months").prop( "disabled", false );
        }
    });

    $("#download_analyse_form_months").change(function () {
        var val = $(this).val();
        if (val == "") {
            $("#download_analyse_form_days").prop( "disabled", true );
            $("#download_analyse_form_days").val($("#target option:first").val());
        }
        if (val != "") {
            $("#download_analyse_form_days").prop( "disabled", false );
        }
    });
});