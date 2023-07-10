import $ from 'jquery';


import '../styles/special_export.scss';
import JSZip from 'jszip';
window.JSZip= JSZip;
import 'datatables.net-buttons-zf/js/buttons.foundation';
import 'datatables.net-responsive/js/dataTables.responsive';
import 'datatables.net-responsive-zf/js/responsive.foundation';
import DataTables from 'datatables.net-zf';





$(document).ready( async function (tableSelector) {

    var t= $('#special_export').DataTable({
        paging:false,
        searching:false,
        info:false,
        responsive:true

    });


    new $.fn.dataTable.Buttons( t, {
        buttons: [

            {
                extend: 'excelHtml5',
                text: 'Excel',
                className:'excelButton',
                messageTop:' Monats Bericht',
                messageBottom:null,
                title:'Monthly Report',
                filename:'monthly report',
                footer:true,
                autoFilter:true,
                sheetName: 'Monthly report',
            }
        ]
    });

    t.buttons(0,null).container()
        .appendTo( $('#special_export_buttons' ));
});
