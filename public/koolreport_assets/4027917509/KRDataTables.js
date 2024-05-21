var KoolReport = KoolReport || {};
KoolReport.KRDataTables = KoolReport.KRDataTables || (function (global) {

    function findMatchedAncestor(el, selector) {
        while (el && ! el.matches(selector)) el = el.parentElement;
        return el;
    }

    var dt = {};

    dt.expandCollapse = function(expandCollapseSpan) {
        // console.log(expandCollapseSpan);
        var isExpand = expandCollapseSpan.classList.contains('group-expand');
        var tr = findMatchedAncestor(expandCollapseSpan, 'tr');
        var isGroupStart = tr.classList.contains('dtrg-start');
        var level = 1 * tr.className.match(/dtrg-level-(\d+)/)[1];
        var tmpTr = tr;
        while (tmpTr) {
            tmpTr = isGroupStart ? tmpTr.nextElementSibling : tmpTr.previousElementSibling;
            if (! tmpTr) break;
            if (tmpTr.classList.contains('dtrg-group')) {
                var tmpLevel = 1 * tmpTr.className.match(/dtrg-level-(\d+)/)[1];
            } else {
                var tmpLevel = 9999;
            }
            if (tmpLevel > level) {
                var layer = tmpTr.dataset.layer || 1;
                layer = 1 * layer;
                if (isExpand) {
                    tmpTr.dataset.layer = layer + 1;
                } else {
                    tmpTr.dataset.layer = layer - 1;
                }
                tmpTr.style.display = 1 * tmpTr.dataset.layer > 0 ? '' : 'none';
            } else if (tmpLevel === level) {
                var matchedExpandSpan = tmpTr.querySelector('.group-expand');
                var matchedCollapseSpan = tmpTr.querySelector('.group-collapse');
                if (matchedExpandSpan && matchedCollapseSpan) {
                    matchedExpandSpan.style.display = isExpand ? 'none' : '';
                    matchedCollapseSpan.style.display = isExpand ? '' : 'none';
                }
                break;
            }
        }
        expandCollapseSpan.style.display = 'none';
        var collpseExpandSpan = isExpand ? 
            expandCollapseSpan.nextElementSibling : expandCollapseSpan.previousElementSibling;
        collpseExpandSpan.style.display = '';
    }

    dt.bindSearchOnEnter = function() {
        var id = this.id;
        var jQdt = global[id];
        var KRdt = this;

        function strToPhrases(str) {
            var phrases = [];
            str = str.replace(/"([^"]*)"/g, function(match, p1, offset, str) {
                if (p1 !== "") phrases.push(p1);
                return "";
            });
            str = str.replace(/[^\s\t]*/g, function(match, offset, str) {
                if (match !== "") phrases.push(match);
                return "";
            });
            return phrases;
        }
    
        function strToRegexStr(str) {
            str = str.trim();
            str = str.replace(/^\s*or\s+/gi, "");
            str = str.replace(/\s+or\s*$/gi, "");
            str = str.replace(/\sor\s/gi, " or ");
            var searches = str.split(' or ');
            var searchRegex = "^";
            for (var i=0; i<searches.length; i+=1) {
                var phrasesRegex = "";
                var phrases = strToPhrases(searches[i]);
                for (var j=0; j<phrases.length; j+=1) {
                    phrasesRegex += "(?=.*" + phrases[j] + ")";
                }
                phrasesRegex += ".+";
                searches[i] = phrasesRegex;
            }
            searchRegex = searches.join('|');
            return searchRegex;
        }

        if (KRdt.searchOnEnter) {
            $('#' + id + '_filter input')
            .unbind()
            .bind('keydown', function (e) {
                if(e.keyCode == 13) {
                    e.preventDefault(); //prevent form submit with enter input
                    if (KRdt.searchMode === 'or' && ! KRdt.serverSide) {
                        var value = $.fn.dataTable.util.escapeRegex(this.value);
                        searchRegex = strToRegexStr(value);
                        jQdt.search(searchRegex, true, false).draw();
                    } else {
                        jQdt.search(this.value).draw();
                    } 
                }
            });
        }

        if (! KRdt.searchOnEnter && KRdt.searchMode === 'or') {
            $('#' + id + '_filter input')
            .unbind()
            .bind('input', function (e) {
                var value = $.fn.dataTable.util.escapeRegex(this.value);
                searchRegex = strToRegexStr(value);
                jQdt.search(searchRegex, true, false).draw();
            });
        }

    }

    dt.init = function (data) {
        for (var p in data)
            if (data.hasOwnProperty(p))
                this[p] = data[p];

        this.bindSearchOnEnter();

        var dtObj = global[this.id];

        //When page change or page length change, reset all rows' layer state 
        //and make them visible
        //Otherwise, hidden rows (with layer <=0) won't ever be shown
        //because all group rows are rerendered at init state and only show collapse icons
        //Page change or page length change event happens before row groups are rendered
        function resetRowLayerFunc( e, settings ) {
            // console.log('page length changed')
            var rows = document.querySelectorAll('#' + this.id + ' tr');
            rows.forEach(function(row) {
                var layer = row.dataset.layer;
                if (layer && 1 * layer < 1) {
                    delete row.dataset.layer;
                    row.style.display = '';
                }
            })
        };
        dtObj.on( 'length.dt', resetRowLayerFunc.bind(this) );
        dtObj.on( 'page.dt', resetRowLayerFunc.bind(this) );

        if (data.rowDetailData) {
            var expandCollapseSelector = data.rowDetailIcon ? 
                'td.details-control i' : 
                (data.rowDetailSelector || 'tbody tr[role="row"]');
            dtObj.on('click', expandCollapseSelector, function () {
                var tr = $(this).closest('tr');
                var row = dtObj.row( tr );
         
                if ( row.child.isShown() ) {
                    // This row is already open - close it
                    row.child.hide();
                    tr.removeClass('shown');
                    if (data.rowDetailIcon)
                        this.classList.replace('fa-minus-square', 'fa-plus-square');
                }
                else {
                    // Open this row
                    var rowArr = row.data();
                    var rowData = {};
                    for (var i=1; i<data.showColumnKeys.length; i+=1) {
                        var colIndex = data.fastRender ? i - 1 : i;
                        rowData[i] = rowData[data.showColumnKeys[i]] = rowArr[colIndex];
                    }
                    rowData['{rowDetailData}'] = rowArr['{rowDetailData}'];
                    row.child( data.rowDetailData(rowData) ).show();
                    tr.addClass('shown');
                    if (data.rowDetailIcon)
                        this.classList.replace('fa-plus-square', 'fa-minus-square');
                }
            } );            
        }
    }

    var KRDataTablesFunctions = (function () {
        return function () {
            for (var p in dt)
                if (dt.hasOwnProperty(p))
                    this[p] = dt[p];
        };
    })();

    var KRDataTables = function () { };
    KRDataTablesFunctions.call(KRDataTables.prototype);

    return {
        create: function (vq_data) {
            var krdt = new KRDataTables();
            krdt.init(vq_data);
            return krdt;
        }
    }
})(window);