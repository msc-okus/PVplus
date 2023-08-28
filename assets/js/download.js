import $ from 'jquery';
import '../styles/special_export.scss';
import JSZip from 'jszip';
window.JSZip= JSZip;
import  pdfMake from 'pdfmake/build/pdfmake';
import pdfFonts from 'pdfmake/build/vfs_fonts';
pdfMake.vfs = pdfFonts.pdfMake.vfs;
import 'datatables.net-buttons-zf/js/buttons.foundation';
import 'datatables.net-buttons/js/buttons.colVis.mjs';
import 'datatables.net-buttons/js/buttons.html5.mjs';
import 'datatables.net-buttons/js/buttons.print.mjs';
import 'datatables.net-responsive/js/dataTables.responsive';
import 'datatables.net-responsive-zf/js/responsive.foundation';
import 'datatables.net-select-zf/js/select.foundation';
import DataTables from 'datatables.net-zf';

$(document).ready( async function (tableSelector) {

    let tx= $('#download ').DataTable({
        paging:false,
        searching:false,
        info:false,
        responsive:true,
        ordering:false

    });
    new $.fn.dataTable.Buttons( tx, {
        buttons: [
            {
                extend: 'excelHtml5',
                text: 'Donwload as Excel',
                className:'excelButton',
                messageTop:' Download Data',
                messageBottom:null,
                title:null,
                filename:'downloaddata',
                footer:true,
                //  autoFilter:true,
                sheetName: 'Download Data',
                exportOptions:{
                    format: {
                        body: function (data, row, column, node) {
                            if(column !== 0) {
                                let arr = data.split(',');
                                if (arr[0].includes('.')){
                                    return arr[0].replaceAll('.','') + '.' + arr[1];
                                }
                                return arr[0] + '.' + arr[1];
                            }
                            return data
                        }
                    }
                }
            }
        ]
    });

    tx.buttons(0,null).container()
        .appendTo( $('#download_buttons' ));

    const $month = $("#download_analyse_form_months");
    const $year  = $("#download_analyse_form_years");
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