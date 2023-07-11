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

    var t= $('#special_export').DataTable({
        paging:false,
        searching:false,
        info:false,
        responsive:true,
        ordering:false

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
              //  autoFilter:true,
                sheetName: 'Monthly report',
                exportOptions:{
                    format: {
                        body: function (data, row, column, node) {

                            if(column !== 0) {


                                var arr = data.split(',');

                                if (arr[0].includes('.')){
                                    return arr[0].replace('.','') + '.' + arr[1];
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

    t.buttons(0,null).container()
        .appendTo( $('#special_export_buttons' ));
});
