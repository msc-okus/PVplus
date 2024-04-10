var KoolReport = KoolReport || {};
KoolReport.ChartTable = KoolReport.ChartTable || (function (global) {
    var ct = {};

    ct.redrawTable = function () {
        this.colMetas = JSON.parse(JSON.stringify(this.originalColMetas));
        this.data = JSON.parse(JSON.stringify(this.originalData));

        console.log('redrawTable -> buildDataTable');
        this.buildDataTable();
    }

    ct.addLabelColumn = function () {
        this.colMetas.unshift({ alias: "Label" });
        for (var i = 0; i < this.data.length; i += 1) {
            this.data[i]["Label"] = "Label_" + i;
        }

        console.log('addLabelColumn -> buildDataTable');
        this.buildDataTable();
    }

    ct.transposeData = function () {
        var headers = this.dom.querySelectorAll(`${this.selector} .data-table thead th`);
        headers = Array.from(headers).map(h => h.textContent.trim());
        var header0 = headers[0];
        var newColMetas = [{ alias: header0 }];
        var newData = [];
        var data = this.dataTable.rows().data();
        for (var i = 0; i < data.length; i += 1) {
            var row = data[i];
            var newColumn = row[header0];
            newColMetas.push({ alias: newColumn });
        }
        for (var i = 1; i < headers.length; i += 1) {
            var header = headers[i];
            var newRow = {};
            newRow[header0] = header;
            for (var j = 1; j < newColMetas.length; j += 1) {
                var newColumn = newColMetas[j].alias;
                var row = data[j - 1];
                newRow['' + newColumn] = row[header];
            }
            newData.push(newRow);
        }
        console.log('newColMetas = ', newColMetas);
        console.log('newData = ', newData);
        this.colMetas = newColMetas;
        this.data = newData;

        console.log('transposeData -> buildDataTable');
        this.buildDataTable();
    }

    ct.buildDataTable = function () {
        var data = this.data;
        var colMetas = this.colMetas;
        if (data && data.length > 0) {
            var columns = colMetas.map(v => {
                return {
                    title: (v.label || v.alias) + "",
                    data: row => row[v.alias],
                    render: v.type === 'number' ? $.fn.dataTable.render.number(
                        v.thousandSeparator || ',',
                        v.decimalPoint || '.',
                        v.decimals || 2,
                        v.prefix || '',
                        v.suffix || ''
                    ) : null,
                    className: v.type === 'number' ? 'text-right' : '',
                }
            });
            // console.log(data, columns);
            if (this.dataTable) {
                //remove column reorder and visibility event listener 
                //because destroy() would invoke them multiple times futilely
                this.dataTable.off('column-reorder');
                this.dataTable.off('column-visibility.dt');
                this.dataTable.destroy();
                $(this.selector + " .data-table").empty();
            }
            this.dataTableDiv.innerHTML = ``;
            var dtConfig = {
                data: data,
                columns: columns,
                // ordering: false,
                order: [],
                colReorder: { realtime: false },
                dom: 'Bfrtlip',
                buttons: [
                    'copyHtml5',
                    'excelHtml5',
                    'csvHtml5',
                    'pdfHtml5',
                    {
                        text: 'JSON',
                        action: function (e, dt, button, config) {
                            var data = dt.buttons.exportData();

                            $.fn.dataTable.fileSave(
                                new Blob([JSON.stringify(data)]),
                                'Export.json'
                            );
                        }
                    },
                    'print',
                    'colvis',
                ],
                select: true,
            };
            var options = JSON.parse(JSON.stringify(this.options || {}));
            var dtConfig = Object.assign(dtConfig, options);
            this.dataTable = $(this.selector + " .data-table").DataTable(dtConfig);
            this.dataTable.on('column-reorder', this.updateChartColumnsSelection.bind(this));
            this.dataTable.on( 'column-visibility.dt', this.updateChartColumnsSelection.bind(this));
        } else {
            if (this.dataTable) {
                this.dataTable.clear().draw();
            } else {
                this.dataTableDiv.innerHTML = "<thead><tr><th></th></tr></thead>";
                this.dataTable = $(`${this.selector} .data-table`).DataTable({
                    "language": {
                        "emptyTable": "No data yet"
                    }
                });
            }
        }
        this.buildChartColumnsSelect(this.colMetas);
    }

    ct.buildChartColumnsSelect = function (colMetas) {
        if (! colMetas) colMetas = this.colMetas;
        this.labelColumnSel.innerHTML = "";
        this.dataColumnsSel.innerHTML = "";
        for (var i = 0; i < colMetas.length; i += 1) {
            var option = this.optionTpl.cloneNode(true);
            option.value = colMetas[i].alias;
            option.textContent = colMetas[i].alias;
            this.labelColumnSel.appendChild(option);
            this.dataColumnsSel.appendChild(option.cloneNode(true));
        }
        $(`${this.selector} .select-label-column`).multiselect('rebuild');
        $(`${this.selector} .select-data-columns`).multiselect('rebuild');
    }

    ct.updateChartColumnsSelection = function () {
        var headers = this.dom.querySelectorAll(`${this.selector} .data-table thead th`);
        headers = Array.from(headers);
        // headers.splice(details.from, 1);
        var colMetas = headers.map(h => ({ alias: h.textContent.trim() }));
        console.log(colMetas);
        this.buildChartColumnsSelect(colMetas);
    }

    ct.generateChart = function (option) {
        var chartCols = Array.from(this.dataColumnsSel.selectedOptions).map(option => {
            return option.value;
        });
        var labelCol = this.labelColumnSel.selectedOptions[0].value;
        var labels = [];
        var datasets = [];
        var tableData = this.dataTable.rows(
            option === 'selected' ? { selected: true } : undefined
        ).data();
        for (var i = 0; i < chartCols.length; i += 1) {
            var col = chartCols[i];
            var data = [];
            for (var j = 0; j < tableData.length; j += 1) {
                data.push(tableData[j][col]);
                if (i === 0) labels.push(this.data[j][labelCol || "Series " + j]);
            }
            var dataset = { label: col, data: data, fill: false };
            datasets.push(dataset);
        }
        if (this.dataChart && this.dataChart.destroy) this.dataChart.destroy();
        this.dataChart = new Chart(this.ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: datasets,
            },
            options: {
                plugins: {
                    colorschemes: {
                        scheme: 'brewer.Paired12'
                    }
                },
                scales: {
                    yAxes: [{
                        display: true,
                        ticks: {
                            suggestedMin: 0,    // minimum will be 0, unless there is a lower value.
                            // OR //
                            // beginAtZero: true   // minimum value will be 0.
                        }
                    }]
                }
            }
        });
    }

    function downloadObjectAsJson(exportObj, exportName) {
        var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(exportObj));
        var downloadAnchorNode = document.createElement('a');
        downloadAnchorNode.setAttribute("href", dataStr);
        downloadAnchorNode.setAttribute("download", exportName + ".json");
        document.body.appendChild(downloadAnchorNode); // required for firefox
        downloadAnchorNode.click();
        downloadAnchorNode.remove();
    }

    function download(content, fileName, contentType) {
        var a = document.createElement("a");
        var file = new Blob([content], { type: contentType });
        a.href = URL.createObjectURL(file);
        a.download = fileName;
        a.click();
    }

    function readSingleFile(e) {
        var file = e.target.files[0];
        if (!file) {
            return;
        }
        var reader = new FileReader();
        reader.onload = function (e) {
            var contents = e.target.result;
            displayContents(contents);
        };
        reader.readAsText(file);
    }

    function displayContents(contents) {
        var element = document.getElementById('file-content');
        element.textContent = contents;
    }

    // document.getElementById('file-input')
    // .addEventListener('change', readSingleFile, false);
    // <input type="file" id="file-input" />
    // <h3>Contents of the file:</h3>
    // <pre id="file-content"></pre>

    var func = function () {
    };

    var init = function (ct_data) {
        for (var p in ct_data)
            if (ct_data.hasOwnProperty(p))
                this[p] = ct_data[p];

        var selector = this.selector = `krwidget[widget-name="${this.name}"]`;
        $(`${selector} .select-label-column`).multiselect({
            numberDisplayed: 5,
            includeSelectAllOption: true,
            enableFiltering: true,
        });
        $(`${selector} .select-data-columns`).multiselect({
            numberDisplayed: 5,
            includeSelectAllOption: true,
            enableFiltering: true,
        });

        this.selectId = 0;

        var dom = this.dom = document.querySelector(selector);
        this.dataTableDiv = dom.querySelector('table.data-table');
        this.optionTpl = dom.querySelector(`.option-template`);
        this.ctx = dom.querySelector(`#${this.name}-data-chart`);
        this.labelColumnSel = dom.querySelector(`.select-label-column`);
        this.dataColumnsSel = dom.querySelector(`.select-data-columns`);

        if (this.data) {
            var columns = this.meta.columns;
            var colKeys = Object.keys(columns);
            this.colMetas = colKeys.map(k => {
                var colMeta = columns[k];
                colMeta.alias = k;
                return colMeta;
            });
            if (this.columns) {
                this.colMetas = this.colMetas.filter(v => {
                    if (this.columns.indexOf(v.alias) > -1) return true;
                    else return false;
                })
            }
            this.originalColMetas = JSON.parse(JSON.stringify(this.colMetas));
            this.originalData = JSON.parse(JSON.stringify(this.data));

            console.log('init -> buildDataTable');            
            this.buildDataTable();
        }
    }

    var charttableFunctions = (function () {
        return function () {
            this.func = func;
            this.init = init;
            for (var p in ct)
                if (ct.hasOwnProperty(p))
                    this[p] = ct[p];
        };
    })();

    var ChartTable = function () { };
    charttableFunctions.call(ChartTable.prototype);

    return {
        create: function (ct_data) {
            var ct = new ChartTable();
            ct.init(ct_data);
            return ct;
        }
    }

})(window);