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

    let t= $('#special_export').DataTable({
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
                //  title:'Monthly Report',
                //   filename:'_monthly report',
                footer:true,
                //  autoFilter:true,
                sheetName: 'Monthly report',
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
                },
                action: function (e, dt, node, config) {
                    // Using jQuery to show the modal and backdrop
                    $('#modalBackdrop').show();
                    $('#exportModal').show();

                    $('#exportFileName').val(getExportFileName());

                    // Binding the click event with jQuery
                    $('#confirmExport').off('click').on('click', () => {
                        // Hide the modal and backdrop using jQuery
                        $('#modalBackdrop').hide();
                        $('#exportModal').hide();

                        // Set the new filename from the input value
                        config.filename = $('#exportFileName').val();
                        config.title = $('#exportFileName').val();

                        // Execute the default export action with the updated configuration
                        $.fn.dataTable.ext.buttons.excelHtml5.action.call(this, e, dt, node, config);
                    });

                    // Binding the click event with jQuery
                    $('#cancelExport').off('click').on('click', () => {
                        // Hide the modal and backdrop using jQuery
                        $('#modalBackdrop').hide();
                        $('#exportModal').hide();
                    });

                }
            }
        ]
    });

    t.buttons(0,null).container()
        .appendTo( $('#special_export_buttons' ));


    // Function to generate the export file name based on the values of userSelectForm fields
    function getExportFileName() {
        const username = $('#anlage-id option:selected').text();  // Get the text of the selected option in the Plant field
        const startDate = $('#start-day').val();  // Get the value of the startDate field
        const endDate = $('#end-day').val();  // Get the value of the endDate field


        let title = 'monthly_report';  // Initialize the title with "Export"
        if (username) {
            title += `_${username}`;  // If username is not empty, add it to the title
        }
        if (startDate) {
            title += `_${startDate}`;  // If startDate is not empty, add it to the title
        }
        if (endDate) {
            title += `_${endDate}`;  // If endDate is not empty, add it to the title
        }

        return title;  // Return the title
    }
});
