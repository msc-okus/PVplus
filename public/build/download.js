/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./assets/js/download.js":
/*!*******************************!*\
  !*** ./assets/js/download.js ***!
  \*******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _styles_special_export_scss__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../styles/special_export.scss */ "./assets/styles/special_export.scss");
/* harmony import */ var jszip__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! jszip */ "./node_modules/jszip/dist/jszip.min.js");
/* harmony import */ var jszip__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(jszip__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var pdfmake_build_pdfmake__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! pdfmake/build/pdfmake */ "./node_modules/pdfmake/build/pdfmake.js");
/* harmony import */ var pdfmake_build_pdfmake__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(pdfmake_build_pdfmake__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var pdfmake_build_vfs_fonts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! pdfmake/build/vfs_fonts */ "./node_modules/pdfmake/build/vfs_fonts.js");
/* harmony import */ var datatables_net_buttons_zf_js_buttons_foundation__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! datatables.net-buttons-zf/js/buttons.foundation */ "./node_modules/datatables.net-buttons-zf/js/buttons.foundation.mjs");
/* harmony import */ var datatables_net_buttons_js_buttons_colVis_mjs__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! datatables.net-buttons/js/buttons.colVis.mjs */ "./node_modules/datatables.net-buttons/js/buttons.colVis.mjs");
/* harmony import */ var datatables_net_buttons_js_buttons_html5_mjs__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! datatables.net-buttons/js/buttons.html5.mjs */ "./node_modules/datatables.net-buttons/js/buttons.html5.mjs");
/* harmony import */ var datatables_net_buttons_js_buttons_print_mjs__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! datatables.net-buttons/js/buttons.print.mjs */ "./node_modules/datatables.net-buttons/js/buttons.print.mjs");
/* harmony import */ var datatables_net_responsive_js_dataTables_responsive__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! datatables.net-responsive/js/dataTables.responsive */ "./node_modules/datatables.net-responsive/js/dataTables.responsive.mjs");
/* harmony import */ var datatables_net_responsive_zf_js_responsive_foundation__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! datatables.net-responsive-zf/js/responsive.foundation */ "./node_modules/datatables.net-responsive-zf/js/responsive.foundation.mjs");
/* harmony import */ var datatables_net_select_zf_js_select_foundation__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! datatables.net-select-zf/js/select.foundation */ "./node_modules/datatables.net-select-zf/js/select.foundation.mjs");
/* harmony import */ var datatables_net_zf__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! datatables.net-zf */ "./node_modules/datatables.net-zf/js/dataTables.foundation.mjs");



window.JSZip = (jszip__WEBPACK_IMPORTED_MODULE_2___default());


(pdfmake_build_pdfmake__WEBPACK_IMPORTED_MODULE_3___default().vfs) = pdfmake_build_vfs_fonts__WEBPACK_IMPORTED_MODULE_4__.pdfMake.vfs;








jquery__WEBPACK_IMPORTED_MODULE_0___default()(document).ready(async function (tableSelector) {
  let t = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#download ').DataTable({
    paging: false,
    searching: false,
    info: false,
    responsive: true,
    ordering: false
  });
  new (jquery__WEBPACK_IMPORTED_MODULE_0___default().fn).dataTable.Buttons(t, {
    buttons: [{
      extend: 'excelHtml5',
      text: 'Download as Excel',
      className: 'excelButton',
      messageTop: ' Download Data',
      messageBottom: null,
      title: null,
      filename: 'downloaddata',
      footer: true,
      //  autoFilter:true,
      sheetName: 'Download Data',
      exportOptions: {
        format: {
          body: function (data, row, column, node) {
            if (column !== 0) {
              let arr = data.split(',');
              if (arr[0].includes('.')) {
                return arr[0].replaceAll('.', '') + '.' + arr[1];
              }
              return arr[0] + '.' + arr[1];
            }
            return data;
          }
        }
      }
    }]
  });
  t.buttons(0, null).container().appendTo(jquery__WEBPACK_IMPORTED_MODULE_0___default()('#download_buttons'));
  const $month = jquery__WEBPACK_IMPORTED_MODULE_0___default()("#download_analyse_form_months");
  const $year = jquery__WEBPACK_IMPORTED_MODULE_0___default()("#download_analyse_form_years");
  const $days = jquery__WEBPACK_IMPORTED_MODULE_0___default()("#download_analyse_form_days");
  var valYears = $year.val();
  var valMonths = $month.val();
  $month.prop("disabled", true);
  $days.prop("disabled", true);
  if (valYears == "") {
    $month.prop("disabled", true);
    $month.val(jquery__WEBPACK_IMPORTED_MODULE_0___default()("#target option:first").val());
    $days.prop("disabled", true);
    $days.val(jquery__WEBPACK_IMPORTED_MODULE_0___default()("#target option:first").val());
  }
  if (valYears != "") {
    $month.prop("disabled", false);
  }
  if (valYears != "" && valMonths != "") {
    $days.prop("disabled", false);
  }
  $year.change(function () {
    var val = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).val();
    if (val == "") {
      $month.prop("disabled", true);
      $month.val(jquery__WEBPACK_IMPORTED_MODULE_0___default()("#target option:first").val());
      $days.prop("disabled", true);
      $days.val(jquery__WEBPACK_IMPORTED_MODULE_0___default()("#target option:first").val());
    }
    if (val != "") {
      $month.prop("disabled", false);
    }
  });
  $month.change(function () {
    var val = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).val();
    if (val == "") {
      $days.prop("disabled", true);
      $days.val(jquery__WEBPACK_IMPORTED_MODULE_0___default()("#target option:first").val());
    }
    if (val != "") {
      $days.prop("disabled", false);
    }
  });
});

/***/ }),

/***/ "./assets/styles/special_export.scss":
/*!*******************************************!*\
  !*** ./assets/styles/special_export.scss ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	(() => {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = (result, chunkIds, fn, priority) => {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var [chunkIds, fn, priority] = deferred[i];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every((key) => (__webpack_require__.O[key](chunkIds[j])))) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					var r = fn();
/******/ 					if (r !== undefined) result = r;
/******/ 				}
/******/ 			}
/******/ 			return result;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/global */
/******/ 	(() => {
/******/ 		__webpack_require__.g = (function() {
/******/ 			if (typeof globalThis === 'object') return globalThis;
/******/ 			try {
/******/ 				return this || new Function('return this')();
/******/ 			} catch (e) {
/******/ 				if (typeof window === 'object') return window;
/******/ 			}
/******/ 		})();
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		// no baseURI
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"download": 0,
/******/ 			"assets_styles_special_export_scss": 0
/******/ 		};
/******/ 		
/******/ 		// no chunk on demand loading
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = (chunkId) => (installedChunks[chunkId] === 0);
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
/******/ 			var [chunkIds, moreModules, runtime] = data;
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some((id) => (installedChunks[id] !== 0))) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkId] = 0;
/******/ 			}
/******/ 			return __webpack_require__.O(result);
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = globalThis["webpackChunk"] = globalThis["webpackChunk"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["vendors-node_modules_jquery_dist_jquery_js","vendors-node_modules_jszip_dist_jszip_min_js-node_modules_pdfmake_build_pdfmake_js-node_modul-8d6267","assets_styles_special_export_scss"], () => (__webpack_require__("./assets/js/download.js")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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QUFnRCxhQUFhO1dBQzdEOzs7OztXQ05BOztXQUVBO1dBQ0E7V0FDQTtXQUNBO1dBQ0E7V0FDQTtXQUNBOztXQUVBOztXQUVBOztXQUVBOztXQUVBOztXQUVBOztXQUVBOztXQUVBO1dBQ0E7V0FDQTtXQUNBO1dBQ0E7V0FDQTtXQUNBO1dBQ0E7V0FDQTtXQUNBO1dBQ0E7V0FDQTtXQUNBO1dBQ0E7V0FDQTtXQUNBLE1BQU0scUJBQXFCO1dBQzNCO1dBQ0E7V0FDQTtXQUNBO1dBQ0E7V0FDQTtXQUNBO1dBQ0E7O1dBRUE7V0FDQTtXQUNBOzs7OztVRWpEQTtVQUNBO1VBQ0E7VUFDQTtVQUNBIiwic291cmNlcyI6WyJ3ZWJwYWNrOi8vLy4vYXNzZXRzL2pzL2Rvd25sb2FkLmpzIiwid2VicGFjazovLy8uL2Fzc2V0cy9zdHlsZXMvc3BlY2lhbF9leHBvcnQuc2NzcyIsIndlYnBhY2s6Ly8vd2VicGFjay9ib290c3RyYXAiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svcnVudGltZS9jaHVuayBsb2FkZWQiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svcnVudGltZS9jb21wYXQgZ2V0IGRlZmF1bHQgZXhwb3J0Iiwid2VicGFjazovLy93ZWJwYWNrL3J1bnRpbWUvZGVmaW5lIHByb3BlcnR5IGdldHRlcnMiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svcnVudGltZS9nbG9iYWwiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svcnVudGltZS9oYXNPd25Qcm9wZXJ0eSBzaG9ydGhhbmQiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svcnVudGltZS9tYWtlIG5hbWVzcGFjZSBvYmplY3QiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svcnVudGltZS9qc29ucCBjaHVuayBsb2FkaW5nIiwid2VicGFjazovLy93ZWJwYWNrL2JlZm9yZS1zdGFydHVwIiwid2VicGFjazovLy93ZWJwYWNrL3N0YXJ0dXAiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svYWZ0ZXItc3RhcnR1cCJdLCJzb3VyY2VzQ29udGVudCI6WyJpbXBvcnQgJCBmcm9tICdqcXVlcnknO1xuaW1wb3J0ICcuLi9zdHlsZXMvc3BlY2lhbF9leHBvcnQuc2Nzcyc7XG5pbXBvcnQgSlNaaXAgZnJvbSAnanN6aXAnO1xud2luZG93LkpTWmlwPSBKU1ppcDtcbmltcG9ydCAgcGRmTWFrZSBmcm9tICdwZGZtYWtlL2J1aWxkL3BkZm1ha2UnO1xuaW1wb3J0IHBkZkZvbnRzIGZyb20gJ3BkZm1ha2UvYnVpbGQvdmZzX2ZvbnRzJztcbnBkZk1ha2UudmZzID0gcGRmRm9udHMucGRmTWFrZS52ZnM7XG5pbXBvcnQgJ2RhdGF0YWJsZXMubmV0LWJ1dHRvbnMtemYvanMvYnV0dG9ucy5mb3VuZGF0aW9uJztcbmltcG9ydCAnZGF0YXRhYmxlcy5uZXQtYnV0dG9ucy9qcy9idXR0b25zLmNvbFZpcy5tanMnO1xuaW1wb3J0ICdkYXRhdGFibGVzLm5ldC1idXR0b25zL2pzL2J1dHRvbnMuaHRtbDUubWpzJztcbmltcG9ydCAnZGF0YXRhYmxlcy5uZXQtYnV0dG9ucy9qcy9idXR0b25zLnByaW50Lm1qcyc7XG5pbXBvcnQgJ2RhdGF0YWJsZXMubmV0LXJlc3BvbnNpdmUvanMvZGF0YVRhYmxlcy5yZXNwb25zaXZlJztcbmltcG9ydCAnZGF0YXRhYmxlcy5uZXQtcmVzcG9uc2l2ZS16Zi9qcy9yZXNwb25zaXZlLmZvdW5kYXRpb24nO1xuaW1wb3J0ICdkYXRhdGFibGVzLm5ldC1zZWxlY3QtemYvanMvc2VsZWN0LmZvdW5kYXRpb24nO1xuaW1wb3J0IERhdGFUYWJsZXMgZnJvbSAnZGF0YXRhYmxlcy5uZXQtemYnO1xuXG4kKGRvY3VtZW50KS5yZWFkeSggYXN5bmMgZnVuY3Rpb24gKHRhYmxlU2VsZWN0b3IpIHtcblxuICAgIGxldCB0PSAkKCcjZG93bmxvYWQgJykuRGF0YVRhYmxlKHtcbiAgICAgICAgcGFnaW5nOmZhbHNlLFxuICAgICAgICBzZWFyY2hpbmc6ZmFsc2UsXG4gICAgICAgIGluZm86ZmFsc2UsXG4gICAgICAgIHJlc3BvbnNpdmU6dHJ1ZSxcbiAgICAgICAgb3JkZXJpbmc6ZmFsc2VcblxuICAgIH0pO1xuICAgIG5ldyAkLmZuLmRhdGFUYWJsZS5CdXR0b25zKCB0LCB7XG4gICAgICAgIGJ1dHRvbnM6IFtcbiAgICAgICAgICAgIHtcbiAgICAgICAgICAgICAgICBleHRlbmQ6ICdleGNlbEh0bWw1JyxcbiAgICAgICAgICAgICAgICB0ZXh0OiAnRG93bmxvYWQgYXMgRXhjZWwnLFxuICAgICAgICAgICAgICAgIGNsYXNzTmFtZTonZXhjZWxCdXR0b24nLFxuICAgICAgICAgICAgICAgIG1lc3NhZ2VUb3A6JyBEb3dubG9hZCBEYXRhJyxcbiAgICAgICAgICAgICAgICBtZXNzYWdlQm90dG9tOm51bGwsXG4gICAgICAgICAgICAgICAgdGl0bGU6bnVsbCxcbiAgICAgICAgICAgICAgICBmaWxlbmFtZTonZG93bmxvYWRkYXRhJyxcbiAgICAgICAgICAgICAgICBmb290ZXI6dHJ1ZSxcbiAgICAgICAgICAgICAgICAvLyAgYXV0b0ZpbHRlcjp0cnVlLFxuICAgICAgICAgICAgICAgIHNoZWV0TmFtZTogJ0Rvd25sb2FkIERhdGEnLFxuICAgICAgICAgICAgICAgIGV4cG9ydE9wdGlvbnM6e1xuICAgICAgICAgICAgICAgICAgICBmb3JtYXQ6IHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGJvZHk6IGZ1bmN0aW9uIChkYXRhLCByb3csIGNvbHVtbiwgbm9kZSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmKGNvbHVtbiAhPT0gMCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBsZXQgYXJyID0gZGF0YS5zcGxpdCgnLCcpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAoYXJyWzBdLmluY2x1ZGVzKCcuJykpe1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIGFyclswXS5yZXBsYWNlQWxsKCcuJywnJykgKyAnLicgKyBhcnJbMV07XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIGFyclswXSArICcuJyArIGFyclsxXTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIGRhdGFcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgXVxuICAgIH0pO1xuXG4gICAgdC5idXR0b25zKDAsbnVsbCkuY29udGFpbmVyKClcbiAgICAgICAgLmFwcGVuZFRvKCAkKCcjZG93bmxvYWRfYnV0dG9ucycgKSk7XG5cbiAgICBjb25zdCAkbW9udGggPSAkKFwiI2Rvd25sb2FkX2FuYWx5c2VfZm9ybV9tb250aHNcIik7XG4gICAgY29uc3QgJHllYXIgID0gJChcIiNkb3dubG9hZF9hbmFseXNlX2Zvcm1feWVhcnNcIik7XG4gICAgY29uc3QgJGRheXMgID0gJChcIiNkb3dubG9hZF9hbmFseXNlX2Zvcm1fZGF5c1wiKTtcbiAgICB2YXIgdmFsWWVhcnMgPSAkeWVhci52YWwoKTtcbiAgICB2YXIgdmFsTW9udGhzID0gJG1vbnRoLnZhbCgpO1xuXG4gICAgJG1vbnRoLnByb3AoIFwiZGlzYWJsZWRcIiwgdHJ1ZSApO1xuICAgICRkYXlzLnByb3AoIFwiZGlzYWJsZWRcIiwgdHJ1ZSApO1xuXG4gICAgaWYgKHZhbFllYXJzID09IFwiXCIpIHtcbiAgICAgICAgJG1vbnRoLnByb3AoIFwiZGlzYWJsZWRcIiwgdHJ1ZSApO1xuICAgICAgICAkbW9udGgudmFsKCQoXCIjdGFyZ2V0IG9wdGlvbjpmaXJzdFwiKS52YWwoKSk7XG4gICAgICAgICRkYXlzLnByb3AoIFwiZGlzYWJsZWRcIiwgdHJ1ZSApO1xuICAgICAgICAkZGF5cy52YWwoJChcIiN0YXJnZXQgb3B0aW9uOmZpcnN0XCIpLnZhbCgpKTtcbiAgICB9XG4gICAgaWYgKHZhbFllYXJzICE9IFwiXCIpIHtcbiAgICAgICAgJG1vbnRoLnByb3AoIFwiZGlzYWJsZWRcIiwgZmFsc2UgKTtcbiAgICB9XG4gICAgaWYgKHZhbFllYXJzICE9IFwiXCIgJiYgIHZhbE1vbnRocyAhPSBcIlwiKSB7XG4gICAgICAgICRkYXlzLnByb3AoIFwiZGlzYWJsZWRcIiwgZmFsc2UgKTtcbiAgICB9XG5cbiAgICAkeWVhci5jaGFuZ2UoZnVuY3Rpb24gKCkge1xuICAgICAgICB2YXIgdmFsID0gJCh0aGlzKS52YWwoKTtcbiAgICAgICAgaWYgKHZhbCA9PSBcIlwiKSB7XG4gICAgICAgICAgICAkbW9udGgucHJvcCggXCJkaXNhYmxlZFwiLCB0cnVlICk7XG4gICAgICAgICAgICAkbW9udGgudmFsKCQoXCIjdGFyZ2V0IG9wdGlvbjpmaXJzdFwiKS52YWwoKSk7XG4gICAgICAgICAgICAkZGF5cy5wcm9wKCBcImRpc2FibGVkXCIsIHRydWUgKTtcbiAgICAgICAgICAgICRkYXlzLnZhbCgkKFwiI3RhcmdldCBvcHRpb246Zmlyc3RcIikudmFsKCkpO1xuICAgICAgICB9XG4gICAgICAgIGlmICh2YWwgIT0gXCJcIikge1xuICAgICAgICAgICAgJG1vbnRoLnByb3AoIFwiZGlzYWJsZWRcIiwgZmFsc2UgKTtcbiAgICAgICAgfVxuICAgIH0pO1xuXG4gICAgJG1vbnRoLmNoYW5nZShmdW5jdGlvbiAoKSB7XG4gICAgICAgIHZhciB2YWwgPSAkKHRoaXMpLnZhbCgpO1xuICAgICAgICBpZiAodmFsID09IFwiXCIpIHtcbiAgICAgICAgICAgICRkYXlzLnByb3AoIFwiZGlzYWJsZWRcIiwgdHJ1ZSApO1xuICAgICAgICAgICAgJGRheXMudmFsKCQoXCIjdGFyZ2V0IG9wdGlvbjpmaXJzdFwiKS52YWwoKSk7XG4gICAgICAgIH1cbiAgICAgICAgaWYgKHZhbCAhPSBcIlwiKSB7XG4gICAgICAgICAgICAkZGF5cy5wcm9wKCBcImRpc2FibGVkXCIsIGZhbHNlICk7XG4gICAgICAgIH1cbiAgICB9KTtcbn0pOyIsIi8vIGV4dHJhY3RlZCBieSBtaW5pLWNzcy1leHRyYWN0LXBsdWdpblxuZXhwb3J0IHt9OyIsIi8vIFRoZSBtb2R1bGUgY2FjaGVcbnZhciBfX3dlYnBhY2tfbW9kdWxlX2NhY2hlX18gPSB7fTtcblxuLy8gVGhlIHJlcXVpcmUgZnVuY3Rpb25cbmZ1bmN0aW9uIF9fd2VicGFja19yZXF1aXJlX18obW9kdWxlSWQpIHtcblx0Ly8gQ2hlY2sgaWYgbW9kdWxlIGlzIGluIGNhY2hlXG5cdHZhciBjYWNoZWRNb2R1bGUgPSBfX3dlYnBhY2tfbW9kdWxlX2NhY2hlX19bbW9kdWxlSWRdO1xuXHRpZiAoY2FjaGVkTW9kdWxlICE9PSB1bmRlZmluZWQpIHtcblx0XHRyZXR1cm4gY2FjaGVkTW9kdWxlLmV4cG9ydHM7XG5cdH1cblx0Ly8gQ3JlYXRlIGEgbmV3IG1vZHVsZSAoYW5kIHB1dCBpdCBpbnRvIHRoZSBjYWNoZSlcblx0dmFyIG1vZHVsZSA9IF9fd2VicGFja19tb2R1bGVfY2FjaGVfX1ttb2R1bGVJZF0gPSB7XG5cdFx0Ly8gbm8gbW9kdWxlLmlkIG5lZWRlZFxuXHRcdC8vIG5vIG1vZHVsZS5sb2FkZWQgbmVlZGVkXG5cdFx0ZXhwb3J0czoge31cblx0fTtcblxuXHQvLyBFeGVjdXRlIHRoZSBtb2R1bGUgZnVuY3Rpb25cblx0X193ZWJwYWNrX21vZHVsZXNfX1ttb2R1bGVJZF0uY2FsbChtb2R1bGUuZXhwb3J0cywgbW9kdWxlLCBtb2R1bGUuZXhwb3J0cywgX193ZWJwYWNrX3JlcXVpcmVfXyk7XG5cblx0Ly8gUmV0dXJuIHRoZSBleHBvcnRzIG9mIHRoZSBtb2R1bGVcblx0cmV0dXJuIG1vZHVsZS5leHBvcnRzO1xufVxuXG4vLyBleHBvc2UgdGhlIG1vZHVsZXMgb2JqZWN0IChfX3dlYnBhY2tfbW9kdWxlc19fKVxuX193ZWJwYWNrX3JlcXVpcmVfXy5tID0gX193ZWJwYWNrX21vZHVsZXNfXztcblxuIiwidmFyIGRlZmVycmVkID0gW107XG5fX3dlYnBhY2tfcmVxdWlyZV9fLk8gPSAocmVzdWx0LCBjaHVua0lkcywgZm4sIHByaW9yaXR5KSA9PiB7XG5cdGlmKGNodW5rSWRzKSB7XG5cdFx0cHJpb3JpdHkgPSBwcmlvcml0eSB8fCAwO1xuXHRcdGZvcih2YXIgaSA9IGRlZmVycmVkLmxlbmd0aDsgaSA+IDAgJiYgZGVmZXJyZWRbaSAtIDFdWzJdID4gcHJpb3JpdHk7IGktLSkgZGVmZXJyZWRbaV0gPSBkZWZlcnJlZFtpIC0gMV07XG5cdFx0ZGVmZXJyZWRbaV0gPSBbY2h1bmtJZHMsIGZuLCBwcmlvcml0eV07XG5cdFx0cmV0dXJuO1xuXHR9XG5cdHZhciBub3RGdWxmaWxsZWQgPSBJbmZpbml0eTtcblx0Zm9yICh2YXIgaSA9IDA7IGkgPCBkZWZlcnJlZC5sZW5ndGg7IGkrKykge1xuXHRcdHZhciBbY2h1bmtJZHMsIGZuLCBwcmlvcml0eV0gPSBkZWZlcnJlZFtpXTtcblx0XHR2YXIgZnVsZmlsbGVkID0gdHJ1ZTtcblx0XHRmb3IgKHZhciBqID0gMDsgaiA8IGNodW5rSWRzLmxlbmd0aDsgaisrKSB7XG5cdFx0XHRpZiAoKHByaW9yaXR5ICYgMSA9PT0gMCB8fCBub3RGdWxmaWxsZWQgPj0gcHJpb3JpdHkpICYmIE9iamVjdC5rZXlzKF9fd2VicGFja19yZXF1aXJlX18uTykuZXZlcnkoKGtleSkgPT4gKF9fd2VicGFja19yZXF1aXJlX18uT1trZXldKGNodW5rSWRzW2pdKSkpKSB7XG5cdFx0XHRcdGNodW5rSWRzLnNwbGljZShqLS0sIDEpO1xuXHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0ZnVsZmlsbGVkID0gZmFsc2U7XG5cdFx0XHRcdGlmKHByaW9yaXR5IDwgbm90RnVsZmlsbGVkKSBub3RGdWxmaWxsZWQgPSBwcmlvcml0eTtcblx0XHRcdH1cblx0XHR9XG5cdFx0aWYoZnVsZmlsbGVkKSB7XG5cdFx0XHRkZWZlcnJlZC5zcGxpY2UoaS0tLCAxKVxuXHRcdFx0dmFyIHIgPSBmbigpO1xuXHRcdFx0aWYgKHIgIT09IHVuZGVmaW5lZCkgcmVzdWx0ID0gcjtcblx0XHR9XG5cdH1cblx0cmV0dXJuIHJlc3VsdDtcbn07IiwiLy8gZ2V0RGVmYXVsdEV4cG9ydCBmdW5jdGlvbiBmb3IgY29tcGF0aWJpbGl0eSB3aXRoIG5vbi1oYXJtb255IG1vZHVsZXNcbl9fd2VicGFja19yZXF1aXJlX18ubiA9IChtb2R1bGUpID0+IHtcblx0dmFyIGdldHRlciA9IG1vZHVsZSAmJiBtb2R1bGUuX19lc01vZHVsZSA/XG5cdFx0KCkgPT4gKG1vZHVsZVsnZGVmYXVsdCddKSA6XG5cdFx0KCkgPT4gKG1vZHVsZSk7XG5cdF9fd2VicGFja19yZXF1aXJlX18uZChnZXR0ZXIsIHsgYTogZ2V0dGVyIH0pO1xuXHRyZXR1cm4gZ2V0dGVyO1xufTsiLCIvLyBkZWZpbmUgZ2V0dGVyIGZ1bmN0aW9ucyBmb3IgaGFybW9ueSBleHBvcnRzXG5fX3dlYnBhY2tfcmVxdWlyZV9fLmQgPSAoZXhwb3J0cywgZGVmaW5pdGlvbikgPT4ge1xuXHRmb3IodmFyIGtleSBpbiBkZWZpbml0aW9uKSB7XG5cdFx0aWYoX193ZWJwYWNrX3JlcXVpcmVfXy5vKGRlZmluaXRpb24sIGtleSkgJiYgIV9fd2VicGFja19yZXF1aXJlX18ubyhleHBvcnRzLCBrZXkpKSB7XG5cdFx0XHRPYmplY3QuZGVmaW5lUHJvcGVydHkoZXhwb3J0cywga2V5LCB7IGVudW1lcmFibGU6IHRydWUsIGdldDogZGVmaW5pdGlvbltrZXldIH0pO1xuXHRcdH1cblx0fVxufTsiLCJfX3dlYnBhY2tfcmVxdWlyZV9fLmcgPSAoZnVuY3Rpb24oKSB7XG5cdGlmICh0eXBlb2YgZ2xvYmFsVGhpcyA9PT0gJ29iamVjdCcpIHJldHVybiBnbG9iYWxUaGlzO1xuXHR0cnkge1xuXHRcdHJldHVybiB0aGlzIHx8IG5ldyBGdW5jdGlvbigncmV0dXJuIHRoaXMnKSgpO1xuXHR9IGNhdGNoIChlKSB7XG5cdFx0aWYgKHR5cGVvZiB3aW5kb3cgPT09ICdvYmplY3QnKSByZXR1cm4gd2luZG93O1xuXHR9XG59KSgpOyIsIl9fd2VicGFja19yZXF1aXJlX18ubyA9IChvYmosIHByb3ApID0+IChPYmplY3QucHJvdG90eXBlLmhhc093blByb3BlcnR5LmNhbGwob2JqLCBwcm9wKSkiLCIvLyBkZWZpbmUgX19lc01vZHVsZSBvbiBleHBvcnRzXG5fX3dlYnBhY2tfcmVxdWlyZV9fLnIgPSAoZXhwb3J0cykgPT4ge1xuXHRpZih0eXBlb2YgU3ltYm9sICE9PSAndW5kZWZpbmVkJyAmJiBTeW1ib2wudG9TdHJpbmdUYWcpIHtcblx0XHRPYmplY3QuZGVmaW5lUHJvcGVydHkoZXhwb3J0cywgU3ltYm9sLnRvU3RyaW5nVGFnLCB7IHZhbHVlOiAnTW9kdWxlJyB9KTtcblx0fVxuXHRPYmplY3QuZGVmaW5lUHJvcGVydHkoZXhwb3J0cywgJ19fZXNNb2R1bGUnLCB7IHZhbHVlOiB0cnVlIH0pO1xufTsiLCIvLyBubyBiYXNlVVJJXG5cbi8vIG9iamVjdCB0byBzdG9yZSBsb2FkZWQgYW5kIGxvYWRpbmcgY2h1bmtzXG4vLyB1bmRlZmluZWQgPSBjaHVuayBub3QgbG9hZGVkLCBudWxsID0gY2h1bmsgcHJlbG9hZGVkL3ByZWZldGNoZWRcbi8vIFtyZXNvbHZlLCByZWplY3QsIFByb21pc2VdID0gY2h1bmsgbG9hZGluZywgMCA9IGNodW5rIGxvYWRlZFxudmFyIGluc3RhbGxlZENodW5rcyA9IHtcblx0XCJkb3dubG9hZFwiOiAwLFxuXHRcImFzc2V0c19zdHlsZXNfc3BlY2lhbF9leHBvcnRfc2Nzc1wiOiAwXG59O1xuXG4vLyBubyBjaHVuayBvbiBkZW1hbmQgbG9hZGluZ1xuXG4vLyBubyBwcmVmZXRjaGluZ1xuXG4vLyBubyBwcmVsb2FkZWRcblxuLy8gbm8gSE1SXG5cbi8vIG5vIEhNUiBtYW5pZmVzdFxuXG5fX3dlYnBhY2tfcmVxdWlyZV9fLk8uaiA9IChjaHVua0lkKSA9PiAoaW5zdGFsbGVkQ2h1bmtzW2NodW5rSWRdID09PSAwKTtcblxuLy8gaW5zdGFsbCBhIEpTT05QIGNhbGxiYWNrIGZvciBjaHVuayBsb2FkaW5nXG52YXIgd2VicGFja0pzb25wQ2FsbGJhY2sgPSAocGFyZW50Q2h1bmtMb2FkaW5nRnVuY3Rpb24sIGRhdGEpID0+IHtcblx0dmFyIFtjaHVua0lkcywgbW9yZU1vZHVsZXMsIHJ1bnRpbWVdID0gZGF0YTtcblx0Ly8gYWRkIFwibW9yZU1vZHVsZXNcIiB0byB0aGUgbW9kdWxlcyBvYmplY3QsXG5cdC8vIHRoZW4gZmxhZyBhbGwgXCJjaHVua0lkc1wiIGFzIGxvYWRlZCBhbmQgZmlyZSBjYWxsYmFja1xuXHR2YXIgbW9kdWxlSWQsIGNodW5rSWQsIGkgPSAwO1xuXHRpZihjaHVua0lkcy5zb21lKChpZCkgPT4gKGluc3RhbGxlZENodW5rc1tpZF0gIT09IDApKSkge1xuXHRcdGZvcihtb2R1bGVJZCBpbiBtb3JlTW9kdWxlcykge1xuXHRcdFx0aWYoX193ZWJwYWNrX3JlcXVpcmVfXy5vKG1vcmVNb2R1bGVzLCBtb2R1bGVJZCkpIHtcblx0XHRcdFx0X193ZWJwYWNrX3JlcXVpcmVfXy5tW21vZHVsZUlkXSA9IG1vcmVNb2R1bGVzW21vZHVsZUlkXTtcblx0XHRcdH1cblx0XHR9XG5cdFx0aWYocnVudGltZSkgdmFyIHJlc3VsdCA9IHJ1bnRpbWUoX193ZWJwYWNrX3JlcXVpcmVfXyk7XG5cdH1cblx0aWYocGFyZW50Q2h1bmtMb2FkaW5nRnVuY3Rpb24pIHBhcmVudENodW5rTG9hZGluZ0Z1bmN0aW9uKGRhdGEpO1xuXHRmb3IoO2kgPCBjaHVua0lkcy5sZW5ndGg7IGkrKykge1xuXHRcdGNodW5rSWQgPSBjaHVua0lkc1tpXTtcblx0XHRpZihfX3dlYnBhY2tfcmVxdWlyZV9fLm8oaW5zdGFsbGVkQ2h1bmtzLCBjaHVua0lkKSAmJiBpbnN0YWxsZWRDaHVua3NbY2h1bmtJZF0pIHtcblx0XHRcdGluc3RhbGxlZENodW5rc1tjaHVua0lkXVswXSgpO1xuXHRcdH1cblx0XHRpbnN0YWxsZWRDaHVua3NbY2h1bmtJZF0gPSAwO1xuXHR9XG5cdHJldHVybiBfX3dlYnBhY2tfcmVxdWlyZV9fLk8ocmVzdWx0KTtcbn1cblxudmFyIGNodW5rTG9hZGluZ0dsb2JhbCA9IGdsb2JhbFRoaXNbXCJ3ZWJwYWNrQ2h1bmtcIl0gPSBnbG9iYWxUaGlzW1wid2VicGFja0NodW5rXCJdIHx8IFtdO1xuY2h1bmtMb2FkaW5nR2xvYmFsLmZvckVhY2god2VicGFja0pzb25wQ2FsbGJhY2suYmluZChudWxsLCAwKSk7XG5jaHVua0xvYWRpbmdHbG9iYWwucHVzaCA9IHdlYnBhY2tKc29ucENhbGxiYWNrLmJpbmQobnVsbCwgY2h1bmtMb2FkaW5nR2xvYmFsLnB1c2guYmluZChjaHVua0xvYWRpbmdHbG9iYWwpKTsiLCIiLCIvLyBzdGFydHVwXG4vLyBMb2FkIGVudHJ5IG1vZHVsZSBhbmQgcmV0dXJuIGV4cG9ydHNcbi8vIFRoaXMgZW50cnkgbW9kdWxlIGRlcGVuZHMgb24gb3RoZXIgbG9hZGVkIGNodW5rcyBhbmQgZXhlY3V0aW9uIG5lZWQgdG8gYmUgZGVsYXllZFxudmFyIF9fd2VicGFja19leHBvcnRzX18gPSBfX3dlYnBhY2tfcmVxdWlyZV9fLk8odW5kZWZpbmVkLCBbXCJ2ZW5kb3JzLW5vZGVfbW9kdWxlc19qcXVlcnlfZGlzdF9qcXVlcnlfanNcIixcInZlbmRvcnMtbm9kZV9tb2R1bGVzX2pzemlwX2Rpc3RfanN6aXBfbWluX2pzLW5vZGVfbW9kdWxlc19wZGZtYWtlX2J1aWxkX3BkZm1ha2VfanMtbm9kZV9tb2R1bC04ZDYyNjdcIixcImFzc2V0c19zdHlsZXNfc3BlY2lhbF9leHBvcnRfc2Nzc1wiXSwgKCkgPT4gKF9fd2VicGFja19yZXF1aXJlX18oXCIuL2Fzc2V0cy9qcy9kb3dubG9hZC5qc1wiKSkpXG5fX3dlYnBhY2tfZXhwb3J0c19fID0gX193ZWJwYWNrX3JlcXVpcmVfXy5PKF9fd2VicGFja19leHBvcnRzX18pO1xuIiwiIl0sIm5hbWVzIjpbIiQiLCJKU1ppcCIsIndpbmRvdyIsInBkZk1ha2UiLCJwZGZGb250cyIsInZmcyIsIkRhdGFUYWJsZXMiLCJkb2N1bWVudCIsInJlYWR5IiwidGFibGVTZWxlY3RvciIsInQiLCJEYXRhVGFibGUiLCJwYWdpbmciLCJzZWFyY2hpbmciLCJpbmZvIiwicmVzcG9uc2l2ZSIsIm9yZGVyaW5nIiwiZm4iLCJkYXRhVGFibGUiLCJCdXR0b25zIiwiYnV0dG9ucyIsImV4dGVuZCIsInRleHQiLCJjbGFzc05hbWUiLCJtZXNzYWdlVG9wIiwibWVzc2FnZUJvdHRvbSIsInRpdGxlIiwiZmlsZW5hbWUiLCJmb290ZXIiLCJzaGVldE5hbWUiLCJleHBvcnRPcHRpb25zIiwiZm9ybWF0IiwiYm9keSIsImRhdGEiLCJyb3ciLCJjb2x1bW4iLCJub2RlIiwiYXJyIiwic3BsaXQiLCJpbmNsdWRlcyIsInJlcGxhY2VBbGwiLCJjb250YWluZXIiLCJhcHBlbmRUbyIsIiRtb250aCIsIiR5ZWFyIiwiJGRheXMiLCJ2YWxZZWFycyIsInZhbCIsInZhbE1vbnRocyIsInByb3AiLCJjaGFuZ2UiXSwic291cmNlUm9vdCI6IiJ9