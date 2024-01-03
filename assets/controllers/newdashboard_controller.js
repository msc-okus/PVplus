import {Controller} from '@hotwired/stimulus';
import $ from 'jquery';
import '../styles/special_export.scss';
import JSZip from 'jszip';
import pdfMake from 'pdfmake/build/pdfmake';
import pdfFonts from 'pdfmake/build/vfs_fonts';
import 'datatables.net-buttons-zf/js/buttons.foundation';
import 'datatables.net-buttons/js/buttons.colVis.mjs';
import 'datatables.net-buttons/js/buttons.html5.mjs';
import 'datatables.net-buttons/js/buttons.print.mjs';
import 'datatables.net-responsive/js/dataTables.responsive';
import 'datatables.net-responsive-zf/js/responsive.foundation';
import 'datatables.net-select-zf/js/select.foundation';

window.JSZip= JSZip;
pdfMake.vfs = pdfFonts.pdfMake.vfs;


export default class extends Controller {
    connect() {
        let t;
        t= $('#new_dashboard').DataTable({
            dom:'lfrtip',
            responsive:true,
            info: false,
            language: { //customize
                lengthMenu: "Show _MENU_"
            },
            columnDefs:[
                { targets: 1, className: 'all' }, // Always show 'Project Nr'
                { targets: 2, className: 'all' }, // Always show 'Plant Name'
                { targets: 3, className: 'all' }, // Always show 'Plant Name'
                { targets: 4,
                    searchable: false // make it non-searchable
                },
                {
                    targets: 5, // Index of the 'Country Name' column
                    visible: false // Set the visibility to false

                },
                { targets: 6, className: 'all' }, // Always show 'Action'
            ],
            "initComplete": function () { // Function to run when table initialization is complete
                let api=this.api();

                const wrapper = $(this).closest('.dataTables_wrapper'); // Get the wrapper div of the DataTable.
                const lengthControl = wrapper.find('.dataTables_length'); // Get the length control element.
                const filterControl = wrapper.find('.dataTables_filter').css('display','flex').css('justify-content','space-between'); // Get the filter control element.
               // const buttons = wrapper.find('.dt-buttons'); // Get the buttons container element.
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
                    if(title !=='Action' && title !=='Flag') {

                        $('<option>')
                            .val(column.index()) // Set the column index as the option value.
                            .text(title) // Set the column title as the option text.
                            .appendTo(columnSelect);
                    }
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
                    .append(filterControl.css('flex', '1 0 70%')) // Set flex to use 50% of the topDiv.
                    //.append(buttons.css('flex', '1 0 35%').css('justify-content','end'))// Set flex to use 40% of the topDiv.
                ;

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

        this.setupCompanyFilter(t);
        this.setupRowSelection('#new_dashboard tbody');
        this.chart();
        this.ticket();
        this.report();
    }
    setupCompanyFilter(table) {
        $('#companyFilter').on('change', function() {
            // Assuming 'company' is the 2nd column
            table.column(1).search(this.value).draw();
        });
    }

    chart() {
        // Attach click event listener to all elements with class 'chart'
        document.querySelectorAll('.chart').forEach(chartElement => {
            chartElement.addEventListener('click', () => {
                // Retrieve the anlageId from the data attribute of the clicked button
                const anlageId = chartElement.getAttribute('data-anlage');
                document.getElementById('anlReport').innerHTML='';
                document.getElementById('anlTicket').innerHTML='';



                // Perform the Fetch request
                fetch(`/newDashboard/plants/${anlageId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.text();
                    })
                    .then(html => {

                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, "text/html");
                        // Insert the plantChart content into the anlChart element
                        document.querySelector('#anlChart').innerHTML = doc.querySelector('#plantChart').innerHTML;


                        this.executeScripts( document.querySelector('#anlChart'));

                    })
                    .catch(error => {
                        console.error('Error:', error);
                        document.querySelector('#anlChart').innerHTML = ' ';
                        document.getElementById('anlReport').innerHTML='';
                        document.getElementById('anlTicket').innerHTML='';
                    });

            });
        });
    }
    ticket(){
        // Attach click event listener to all elements with class 'chart'
        document.querySelectorAll('.ticket-btn').forEach(ticketElement => {
            ticketElement.addEventListener('click', () => {
                // Retrieve the anlageId from the data attribute of the clicked button
                const anlageId = ticketElement.getAttribute('data-anlage');

                document.getElementById('anlReport').innerHTML='';
                document.getElementById('anlChart').innerHTML='';
                document.querySelector('#loadingGif').style.display='flex';


               // Perform the Fetch request
                fetch(`/ticket/list/${anlageId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.text();
                    })
                    .then(html => {

                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, "text/html");

                        document.querySelector('#loadingGif').style.display='none';
                        // Insert the plantChart content into the anlChart element
                        document.querySelector('#anlTicket').innerHTML = doc.querySelector('#anlTicket').innerHTML;


                        if ($.fn.DataTable.isDataTable('#anlTicketContent')) {
                            $('#anlTicketContent').DataTable().clear().destroy();
                        }
                        $('#anlTicketContent').DataTable({
                            dom:'lfrtip',
                            responsive:true,
                            info: false,
                            language: { //customize
                                lengthMenu: "Show _MENU_"
                            },
                            "initComplete": function () { // Function to run when table initialization is complete
                                let api=this.api();

                                const wrapper = $(this).closest('.dataTables_wrapper'); // Get the wrapper div of the DataTable.
                                const lengthControl = wrapper.find('.dataTables_length'); // Get the length control element.
                                const filterControl = wrapper.find('.dataTables_filter').css('display','flex').css('justify-content','space-between'); // Get the filter control element.
                                // const buttons = wrapper.find('.dt-buttons'); // Get the buttons container element.
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
                                    if(title !=='Action') {

                                        $('<option>')
                                            .val(column.index()) // Set the column index as the option value.
                                            .text(title) // Set the column title as the option text.
                                            .appendTo(columnSelect);
                                    }
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
                                        .append(filterControl.css('flex', '1 0 70%')) // Set flex to use 50% of the topDiv.
                                    //.append(buttons.css('flex', '1 0 35%').css('justify-content','end'))// Set flex to use 40% of the topDiv.
                                ;

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
                        this.setupRowSelection('#anlTicketContent tbody');

                    })
                    .catch(error => {
                        console.error('Error:', error);
                        document.getElementById('anlReport').innerHTML='';
                        document.getElementById('anlTicket').innerHTML='';
                        document.getElementById('anlChart').innerHTML='';
                    });

            });
        });
    }
    report(){
        // Attach click event listener to all elements with class 'chart'
        document.querySelectorAll('.report-btn').forEach(reportElement => {
            reportElement.addEventListener('click', () => {
                // Retrieve the anlageId from the data attribute of the clicked button
                const anlageId = reportElement.getAttribute('data-anlage');

                document.getElementById('anlTicket').innerHTML='';
                document.getElementById('anlChart').innerHTML='';
                document.querySelector('#loadingGif').style.display='flex';


                // Perform the Fetch request
                fetch(`/reporting/list/${anlageId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.text();
                    })
                    .then(html => {

                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, "text/html");

                        document.querySelector('#loadingGif').style.display='none';
                        // Insert the plantChart content into the anlChart element
                        document.querySelector('#anlReport').innerHTML = doc.querySelector('#anlReport').innerHTML;


                        if ($.fn.DataTable.isDataTable('#anlReportContent')) {
                            $('#anlReportContent').DataTable().clear().destroy();
                        }
                        $('#anlReportContent').DataTable({
                            dom:'lfrtip',
                            responsive:true,
                            info: false,
                            language: { //customize
                                lengthMenu: "Show _MENU_"
                            },
                            "initComplete": function () { // Function to run when table initialization is complete
                                let api=this.api();

                                const wrapper = $(this).closest('.dataTables_wrapper'); // Get the wrapper div of the DataTable.
                                const lengthControl = wrapper.find('.dataTables_length'); // Get the length control element.
                                const filterControl = wrapper.find('.dataTables_filter').css('display','flex').css('justify-content','space-between'); // Get the filter control element.
                                // const buttons = wrapper.find('.dt-buttons'); // Get the buttons container element.
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
                                    if(title !=='Action') {

                                        $('<option>')
                                            .val(column.index()) // Set the column index as the option value.
                                            .text(title) // Set the column title as the option text.
                                            .appendTo(columnSelect);
                                    }
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
                                        .append(filterControl.css('flex', '1 0 70%')) // Set flex to use 50% of the topDiv.
                                    //.append(buttons.css('flex', '1 0 35%').css('justify-content','end'))// Set flex to use 40% of the topDiv.
                                ;

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

                        this.setupRowSelection('#anlReportContent tbody');

                    })
                    .catch(error => {
                        console.error('Error:', error);
                        document.getElementById('anlReport').innerHTML='';
                        document.getElementById('anlChart').innerHTML='';
                        document.getElementById('anlTicket').innerHTML='';
                    });

            });
        });
    }
    executeScripts(container) {
        container.querySelectorAll('script').forEach((script) => {
            const newScript = document.createElement('script');
            Array.from(script.attributes)
                .forEach(attr => newScript.setAttribute(attr.name, attr.value));
            newScript.appendChild(document.createTextNode(script.innerHTML));
            script.parentNode.replaceChild(newScript, script);
        });
    }
    // Function to setup row selection
    setupRowSelection(selector) {
        $(selector).on('click', 'tr', function() {
            if ($(this).hasClass('selected')) {

            } else {
                // Deselect any currently selected row
                $(selector).find('tr.selected').removeClass('selected');
                // Select the clicked row
                $(this).addClass('selected');
            }
        });
    }


}
