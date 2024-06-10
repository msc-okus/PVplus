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

    let t;
        t= $('#new_dashboard').DataTable({
        dom:'Blfrtip',
        info: false,
        language: { //customize
            lengthMenu: "Show _MENU_"
        },
        "initComplete": function () { // Function to run when table initialization is complete
            let api=this.api();

            const wrapper = $(this).closest('.dataTables_wrapper'); // Get the wrapper div of the DataTable.
            const lengthControl = wrapper.find('.dataTables_length'); // Get the length control element.
            const filterControl = wrapper.find('.dataTables_filter').css('display','flex').css('justify-content','space-between'); // Get the filter control element.
            const buttons = wrapper.find('.dt-buttons'); // Get the buttons container element.
            const pageInfo = wrapper.find('.dataTables_info'); // Get the page info element.
            const paginationControl = wrapper.find('.dataTables_paginate'); // Get the pagination control element.

            const searchInput = filterControl.find('input[type="search"]'); // Get the search input element.
            searchInput.attr("style","width:100% !important;display:block !important").attr('placeholder', 'Search'); // Set the placeholder text for the search input.
            const searchInputLabel=filterControl.find('label');
            searchInputLabel.attr("style","width:100% !important;display:block !important").css('flex','1 0 80%');
            searchInputLabel.contents().filter(function () {
                return this.nodeType === 3; // Node.TEXT_NODE
            }).remove(); // Remove the label text of the filter control.

            // Add a column selector next to the search input.
            const columnSelect = $('<select>')
                .css('flex','1 0 20%')// Set flex to use 30% of the filterControl.
                .on('change', function () { // Event handler for column selector change.
                    const columnIndex = $(this).val(); // Get the selected column index.
                    searchInput.data('columnIndex', columnIndex); // Store the column index in the search input data.
                    searchInput.trigger('input'); // Trigger the input event of the search input.
                });

            // Add an "All" option for searching in all columns.
            $('<option>')
                .val('')
                .text('Column')
                .appendTo(columnSelect);

            // Add an option for each column.
            api.columns().every(function () {
                const column = this;
                const title = $(column.header()).text(); // Get the column title.
                $('<option>')
                    .val(column.index()) // Set the column index as the option value.
                    .text(title) // Set the column title as the option text.
                    .appendTo(columnSelect);
            });

            filterControl.prepend(columnSelect); // Add the column selector to the filter control.




            // Modify the search behavior to use the column selector.
            searchInput.on('input', function () {
                const columnIndex = $(this).data('columnIndex'); // Get the selected column index.
                if (columnIndex === undefined || columnIndex === '') {
                    api.search(this.value).draw(); // Search in all columns.
                } else {
                    api.column(columnIndex).search(this.value).draw(); // Search in the selected column.
                }
            });

            // Create a top div and add the length control, filter control, and buttons.
            const topDiv = $('<div/>')
                .css('display','flex')
                .append(lengthControl.css('flex', '1 0 15%')) // Set flex to use 10% of the topDiv.
                .append(filterControl.css('flex', '1 0 40%')) // Set flex to use 50% of the topDiv.
                .append(buttons.css('flex', '1 0 35%').css('justify-content','end')); // Set flex to use 40% of the topDiv.

            // Create a bottom div and add the page info and pagination control.
            const bottomDiv = $('<div/>')
                .css('display','flex')
                .css('justify-content','end')
                .append(pageInfo)
                .append(paginationControl);

            wrapper.prepend(topDiv); // Add the top div to the wrapper.
            wrapper.append(bottomDiv); // Add the bottom div to the wrapper.
        }

    });


    setupCompanyFilter(t);
    chart();

});

function setupCompanyFilter(table) {
    $('#companyFilter').on('change', function() {
        // Assuming 'company' is the 2nd column
        table.column(1).search(this.value).draw();
    });
}

function  chart(){


    // Attach click event listener to all elements with class 'chart'
    $('.chart').click(function() {
        // Retrieve the anlageId from the data attribute of the clicked button
        var anlageId = $(this).data('anlage');


        // Perform the AJAX request
        $.ajax({
            url: `/newDashboard/plants/${anlageId}`,
            type: 'GET',
            dataType: 'html',
            contentType: 'text/plain; charset=utf-8',
            success: function (data, status, xhr) {

                // Extract the plantchart content from the response
                // Insert the plantchart content into the anlChart element
               // $('#anlChart').html($(data).find('#plantChart').html());
                // Make the anlChart and modalBackdropAnlChart elements visible
              //  $('#anlChart, #modalBackdropAnlChart').show();
            },
            error: function (jqXhr, textStatus, errorMessage) {
                $('#anlChart').html(' ');
            }

        });
    });
}
