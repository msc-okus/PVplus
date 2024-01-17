/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./assets/js/special_export.js":
/*!*************************************!*\
  !*** ./assets/js/special_export.js ***!
  \*************************************/
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
  let t = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#special_export').DataTable({
    paging: false,
    searching: false,
    info: false,
    responsive: true,
    ordering: false
  });
  new (jquery__WEBPACK_IMPORTED_MODULE_0___default().fn).dataTable.Buttons(t, {
    buttons: [{
      extend: 'excelHtml5',
      text: 'Excel',
      className: 'excelButton',
      messageTop: ' Monats Bericht',
      messageBottom: null,
      title: 'Monthly Report',
      filename: '_monthly report',
      footer: true,
      //  autoFilter:true,
      sheetName: 'Monthly report',
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
  t.buttons(0, null).container().appendTo(jquery__WEBPACK_IMPORTED_MODULE_0___default()('#special_export_buttons'));
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
/******/ 			"special_export": 0,
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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["vendors-node_modules_jquery_dist_jquery_js","vendors-node_modules_jszip_dist_jszip_min_js-node_modules_pdfmake_build_pdfmake_js-node_modul-8d6267","assets_styles_special_export_scss"], () => (__webpack_require__("./assets/js/special_export.js")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoic3BlY2lhbF9leHBvcnQuanMiLCJtYXBwaW5ncyI6Ijs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7O0FBQXVCO0FBQ2dCO0FBQ2I7QUFDMUJFLE1BQU0sQ0FBQ0QsS0FBSyxHQUFFQSw4Q0FBSztBQUMwQjtBQUNFO0FBQy9DRSxrRUFBVyxHQUFHQyw0REFBZ0IsQ0FBQ0MsR0FBRztBQUN1QjtBQUNIO0FBQ0Q7QUFDQTtBQUNPO0FBQ0c7QUFDUjtBQUNaO0FBRTNDTCw2Q0FBQyxDQUFDTyxRQUFRLENBQUMsQ0FBQ0MsS0FBSyxDQUFFLGdCQUFnQkMsYUFBYSxFQUFFO0VBRTlDLElBQUlDLENBQUMsR0FBRVYsNkNBQUMsQ0FBQyxpQkFBaUIsQ0FBQyxDQUFDVyxTQUFTLENBQUM7SUFDbENDLE1BQU0sRUFBQyxLQUFLO0lBQ1pDLFNBQVMsRUFBQyxLQUFLO0lBQ2ZDLElBQUksRUFBQyxLQUFLO0lBQ1ZDLFVBQVUsRUFBQyxJQUFJO0lBQ2ZDLFFBQVEsRUFBQztFQUNiLENBQUMsQ0FBQztFQUdGLElBQUloQixrREFBSSxDQUFDa0IsU0FBUyxDQUFDQyxPQUFPLENBQUVULENBQUMsRUFBRTtJQUMzQlUsT0FBTyxFQUFFLENBQ0w7TUFDSUMsTUFBTSxFQUFFLFlBQVk7TUFDcEJDLElBQUksRUFBRSxPQUFPO01BQ2JDLFNBQVMsRUFBQyxhQUFhO01BQ3ZCQyxVQUFVLEVBQUMsaUJBQWlCO01BQzVCQyxhQUFhLEVBQUMsSUFBSTtNQUNsQkMsS0FBSyxFQUFDLGdCQUFnQjtNQUN0QkMsUUFBUSxFQUFDLGlCQUFpQjtNQUMxQkMsTUFBTSxFQUFDLElBQUk7TUFDYjtNQUNFQyxTQUFTLEVBQUUsZ0JBQWdCO01BQzNCQyxhQUFhLEVBQUM7UUFDVkMsTUFBTSxFQUFFO1VBQ0pDLElBQUksRUFBRSxTQUFBQSxDQUFVQyxJQUFJLEVBQUVDLEdBQUcsRUFBRUMsTUFBTSxFQUFFQyxJQUFJLEVBQUU7WUFDckMsSUFBR0QsTUFBTSxLQUFLLENBQUMsRUFBRTtjQUNiLElBQUlFLEdBQUcsR0FBR0osSUFBSSxDQUFDSyxLQUFLLENBQUMsR0FBRyxDQUFDO2NBQ3pCLElBQUlELEdBQUcsQ0FBQyxDQUFDLENBQUMsQ0FBQ0UsUUFBUSxDQUFDLEdBQUcsQ0FBQyxFQUFDO2dCQUNyQixPQUFPRixHQUFHLENBQUMsQ0FBQyxDQUFDLENBQUNHLFVBQVUsQ0FBQyxHQUFHLEVBQUMsRUFBRSxDQUFDLEdBQUcsR0FBRyxHQUFHSCxHQUFHLENBQUMsQ0FBQyxDQUFDO2NBQ25EO2NBQ0EsT0FBT0EsR0FBRyxDQUFDLENBQUMsQ0FBQyxHQUFHLEdBQUcsR0FBR0EsR0FBRyxDQUFDLENBQUMsQ0FBQztZQUNoQztZQUNBLE9BQU9KLElBQUk7VUFDZjtRQUNKO01BQ0o7SUFDSixDQUFDO0VBRVQsQ0FBQyxDQUFDO0VBRUZ2QixDQUFDLENBQUNVLE9BQU8sQ0FBQyxDQUFDLEVBQUMsSUFBSSxDQUFDLENBQUNxQixTQUFTLENBQUMsQ0FBQyxDQUN4QkMsUUFBUSxDQUFFMUMsNkNBQUMsQ0FBQyx5QkFBMEIsQ0FBQyxDQUFDO0FBQ2pELENBQUMsQ0FBQzs7Ozs7Ozs7Ozs7QUM1REY7Ozs7Ozs7VUNBQTtVQUNBOztVQUVBO1VBQ0E7VUFDQTtVQUNBO1VBQ0E7VUFDQTtVQUNBO1VBQ0E7VUFDQTtVQUNBO1VBQ0E7VUFDQTtVQUNBOztVQUVBO1VBQ0E7O1VBRUE7VUFDQTtVQUNBOztVQUVBO1VBQ0E7Ozs7O1dDekJBO1dBQ0E7V0FDQTtXQUNBO1dBQ0EsK0JBQStCLHdDQUF3QztXQUN2RTtXQUNBO1dBQ0E7V0FDQTtXQUNBLGlCQUFpQixxQkFBcUI7V0FDdEM7V0FDQTtXQUNBLGtCQUFrQixxQkFBcUI7V0FDdkM7V0FDQTtXQUNBLEtBQUs7V0FDTDtXQUNBO1dBQ0E7V0FDQTtXQUNBO1dBQ0E7V0FDQTtXQUNBO1dBQ0E7V0FDQTtXQUNBO1dBQ0E7Ozs7O1dDM0JBO1dBQ0E7V0FDQTtXQUNBO1dBQ0E7V0FDQSxpQ0FBaUMsV0FBVztXQUM1QztXQUNBOzs7OztXQ1BBO1dBQ0E7V0FDQTtXQUNBO1dBQ0EseUNBQXlDLHdDQUF3QztXQUNqRjtXQUNBO1dBQ0E7Ozs7O1dDUEE7V0FDQTtXQUNBO1dBQ0E7V0FDQSxHQUFHO1dBQ0g7V0FDQTtXQUNBLENBQUM7Ozs7O1dDUEQ7Ozs7O1dDQUE7V0FDQTtXQUNBO1dBQ0EsdURBQXVELGlCQUFpQjtXQUN4RTtXQUNBLGdEQUFnRCxhQUFhO1dBQzdEOzs7OztXQ05BOztXQUVBO1dBQ0E7V0FDQTtXQUNBO1dBQ0E7V0FDQTtXQUNBOztXQUVBOztXQUVBOztXQUVBOztXQUVBOztXQUVBOztXQUVBOztXQUVBO1dBQ0E7V0FDQTtXQUNBO1dBQ0E7V0FDQTtXQUNBO1dBQ0E7V0FDQTtXQUNBO1dBQ0E7V0FDQTtXQUNBO1dBQ0E7V0FDQTtXQUNBLE1BQU0scUJBQXFCO1dBQzNCO1dBQ0E7V0FDQTtXQUNBO1dBQ0E7V0FDQTtXQUNBO1dBQ0E7O1dBRUE7V0FDQTtXQUNBOzs7OztVRWpEQTtVQUNBO1VBQ0E7VUFDQTtVQUNBIiwic291cmNlcyI6WyJ3ZWJwYWNrOi8vLy4vYXNzZXRzL2pzL3NwZWNpYWxfZXhwb3J0LmpzIiwid2VicGFjazovLy8uL2Fzc2V0cy9zdHlsZXMvc3BlY2lhbF9leHBvcnQuc2NzcyIsIndlYnBhY2s6Ly8vd2VicGFjay9ib290c3RyYXAiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svcnVudGltZS9jaHVuayBsb2FkZWQiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svcnVudGltZS9jb21wYXQgZ2V0IGRlZmF1bHQgZXhwb3J0Iiwid2VicGFjazovLy93ZWJwYWNrL3J1bnRpbWUvZGVmaW5lIHByb3BlcnR5IGdldHRlcnMiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svcnVudGltZS9nbG9iYWwiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svcnVudGltZS9oYXNPd25Qcm9wZXJ0eSBzaG9ydGhhbmQiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svcnVudGltZS9tYWtlIG5hbWVzcGFjZSBvYmplY3QiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svcnVudGltZS9qc29ucCBjaHVuayBsb2FkaW5nIiwid2VicGFjazovLy93ZWJwYWNrL2JlZm9yZS1zdGFydHVwIiwid2VicGFjazovLy93ZWJwYWNrL3N0YXJ0dXAiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svYWZ0ZXItc3RhcnR1cCJdLCJzb3VyY2VzQ29udGVudCI6WyJpbXBvcnQgJCBmcm9tICdqcXVlcnknO1xuaW1wb3J0ICcuLi9zdHlsZXMvc3BlY2lhbF9leHBvcnQuc2Nzcyc7XG5pbXBvcnQgSlNaaXAgZnJvbSAnanN6aXAnO1xud2luZG93LkpTWmlwPSBKU1ppcDtcbmltcG9ydCAgcGRmTWFrZSBmcm9tICdwZGZtYWtlL2J1aWxkL3BkZm1ha2UnO1xuaW1wb3J0IHBkZkZvbnRzIGZyb20gJ3BkZm1ha2UvYnVpbGQvdmZzX2ZvbnRzJztcbnBkZk1ha2UudmZzID0gcGRmRm9udHMucGRmTWFrZS52ZnM7XG5pbXBvcnQgJ2RhdGF0YWJsZXMubmV0LWJ1dHRvbnMtemYvanMvYnV0dG9ucy5mb3VuZGF0aW9uJztcbmltcG9ydCAnZGF0YXRhYmxlcy5uZXQtYnV0dG9ucy9qcy9idXR0b25zLmNvbFZpcy5tanMnO1xuaW1wb3J0ICdkYXRhdGFibGVzLm5ldC1idXR0b25zL2pzL2J1dHRvbnMuaHRtbDUubWpzJztcbmltcG9ydCAnZGF0YXRhYmxlcy5uZXQtYnV0dG9ucy9qcy9idXR0b25zLnByaW50Lm1qcyc7XG5pbXBvcnQgJ2RhdGF0YWJsZXMubmV0LXJlc3BvbnNpdmUvanMvZGF0YVRhYmxlcy5yZXNwb25zaXZlJztcbmltcG9ydCAnZGF0YXRhYmxlcy5uZXQtcmVzcG9uc2l2ZS16Zi9qcy9yZXNwb25zaXZlLmZvdW5kYXRpb24nO1xuaW1wb3J0ICdkYXRhdGFibGVzLm5ldC1zZWxlY3QtemYvanMvc2VsZWN0LmZvdW5kYXRpb24nO1xuaW1wb3J0IERhdGFUYWJsZXMgZnJvbSAnZGF0YXRhYmxlcy5uZXQtemYnO1xuXG4kKGRvY3VtZW50KS5yZWFkeSggYXN5bmMgZnVuY3Rpb24gKHRhYmxlU2VsZWN0b3IpIHtcblxuICAgIGxldCB0PSAkKCcjc3BlY2lhbF9leHBvcnQnKS5EYXRhVGFibGUoe1xuICAgICAgICBwYWdpbmc6ZmFsc2UsXG4gICAgICAgIHNlYXJjaGluZzpmYWxzZSxcbiAgICAgICAgaW5mbzpmYWxzZSxcbiAgICAgICAgcmVzcG9uc2l2ZTp0cnVlLFxuICAgICAgICBvcmRlcmluZzpmYWxzZVxuICAgIH0pO1xuXG5cbiAgICBuZXcgJC5mbi5kYXRhVGFibGUuQnV0dG9ucyggdCwge1xuICAgICAgICBidXR0b25zOiBbXG4gICAgICAgICAgICB7XG4gICAgICAgICAgICAgICAgZXh0ZW5kOiAnZXhjZWxIdG1sNScsXG4gICAgICAgICAgICAgICAgdGV4dDogJ0V4Y2VsJyxcbiAgICAgICAgICAgICAgICBjbGFzc05hbWU6J2V4Y2VsQnV0dG9uJyxcbiAgICAgICAgICAgICAgICBtZXNzYWdlVG9wOicgTW9uYXRzIEJlcmljaHQnLFxuICAgICAgICAgICAgICAgIG1lc3NhZ2VCb3R0b206bnVsbCxcbiAgICAgICAgICAgICAgICB0aXRsZTonTW9udGhseSBSZXBvcnQnLFxuICAgICAgICAgICAgICAgIGZpbGVuYW1lOidfbW9udGhseSByZXBvcnQnLFxuICAgICAgICAgICAgICAgIGZvb3Rlcjp0cnVlLFxuICAgICAgICAgICAgICAvLyAgYXV0b0ZpbHRlcjp0cnVlLFxuICAgICAgICAgICAgICAgIHNoZWV0TmFtZTogJ01vbnRobHkgcmVwb3J0JyxcbiAgICAgICAgICAgICAgICBleHBvcnRPcHRpb25zOntcbiAgICAgICAgICAgICAgICAgICAgZm9ybWF0OiB7XG4gICAgICAgICAgICAgICAgICAgICAgICBib2R5OiBmdW5jdGlvbiAoZGF0YSwgcm93LCBjb2x1bW4sIG5vZGUpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZihjb2x1bW4gIT09IDApIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgbGV0IGFyciA9IGRhdGEuc3BsaXQoJywnKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKGFyclswXS5pbmNsdWRlcygnLicpKXtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiBhcnJbMF0ucmVwbGFjZUFsbCgnLicsJycpICsgJy4nICsgYXJyWzFdO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiBhcnJbMF0gKyAnLicgKyBhcnJbMV07XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiBkYXRhXG4gICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgIF1cbiAgICB9KTtcblxuICAgIHQuYnV0dG9ucygwLG51bGwpLmNvbnRhaW5lcigpXG4gICAgICAgIC5hcHBlbmRUbyggJCgnI3NwZWNpYWxfZXhwb3J0X2J1dHRvbnMnICkpO1xufSk7XG4iLCIvLyBleHRyYWN0ZWQgYnkgbWluaS1jc3MtZXh0cmFjdC1wbHVnaW5cbmV4cG9ydCB7fTsiLCIvLyBUaGUgbW9kdWxlIGNhY2hlXG52YXIgX193ZWJwYWNrX21vZHVsZV9jYWNoZV9fID0ge307XG5cbi8vIFRoZSByZXF1aXJlIGZ1bmN0aW9uXG5mdW5jdGlvbiBfX3dlYnBhY2tfcmVxdWlyZV9fKG1vZHVsZUlkKSB7XG5cdC8vIENoZWNrIGlmIG1vZHVsZSBpcyBpbiBjYWNoZVxuXHR2YXIgY2FjaGVkTW9kdWxlID0gX193ZWJwYWNrX21vZHVsZV9jYWNoZV9fW21vZHVsZUlkXTtcblx0aWYgKGNhY2hlZE1vZHVsZSAhPT0gdW5kZWZpbmVkKSB7XG5cdFx0cmV0dXJuIGNhY2hlZE1vZHVsZS5leHBvcnRzO1xuXHR9XG5cdC8vIENyZWF0ZSBhIG5ldyBtb2R1bGUgKGFuZCBwdXQgaXQgaW50byB0aGUgY2FjaGUpXG5cdHZhciBtb2R1bGUgPSBfX3dlYnBhY2tfbW9kdWxlX2NhY2hlX19bbW9kdWxlSWRdID0ge1xuXHRcdC8vIG5vIG1vZHVsZS5pZCBuZWVkZWRcblx0XHQvLyBubyBtb2R1bGUubG9hZGVkIG5lZWRlZFxuXHRcdGV4cG9ydHM6IHt9XG5cdH07XG5cblx0Ly8gRXhlY3V0ZSB0aGUgbW9kdWxlIGZ1bmN0aW9uXG5cdF9fd2VicGFja19tb2R1bGVzX19bbW9kdWxlSWRdLmNhbGwobW9kdWxlLmV4cG9ydHMsIG1vZHVsZSwgbW9kdWxlLmV4cG9ydHMsIF9fd2VicGFja19yZXF1aXJlX18pO1xuXG5cdC8vIFJldHVybiB0aGUgZXhwb3J0cyBvZiB0aGUgbW9kdWxlXG5cdHJldHVybiBtb2R1bGUuZXhwb3J0cztcbn1cblxuLy8gZXhwb3NlIHRoZSBtb2R1bGVzIG9iamVjdCAoX193ZWJwYWNrX21vZHVsZXNfXylcbl9fd2VicGFja19yZXF1aXJlX18ubSA9IF9fd2VicGFja19tb2R1bGVzX187XG5cbiIsInZhciBkZWZlcnJlZCA9IFtdO1xuX193ZWJwYWNrX3JlcXVpcmVfXy5PID0gKHJlc3VsdCwgY2h1bmtJZHMsIGZuLCBwcmlvcml0eSkgPT4ge1xuXHRpZihjaHVua0lkcykge1xuXHRcdHByaW9yaXR5ID0gcHJpb3JpdHkgfHwgMDtcblx0XHRmb3IodmFyIGkgPSBkZWZlcnJlZC5sZW5ndGg7IGkgPiAwICYmIGRlZmVycmVkW2kgLSAxXVsyXSA+IHByaW9yaXR5OyBpLS0pIGRlZmVycmVkW2ldID0gZGVmZXJyZWRbaSAtIDFdO1xuXHRcdGRlZmVycmVkW2ldID0gW2NodW5rSWRzLCBmbiwgcHJpb3JpdHldO1xuXHRcdHJldHVybjtcblx0fVxuXHR2YXIgbm90RnVsZmlsbGVkID0gSW5maW5pdHk7XG5cdGZvciAodmFyIGkgPSAwOyBpIDwgZGVmZXJyZWQubGVuZ3RoOyBpKyspIHtcblx0XHR2YXIgW2NodW5rSWRzLCBmbiwgcHJpb3JpdHldID0gZGVmZXJyZWRbaV07XG5cdFx0dmFyIGZ1bGZpbGxlZCA9IHRydWU7XG5cdFx0Zm9yICh2YXIgaiA9IDA7IGogPCBjaHVua0lkcy5sZW5ndGg7IGorKykge1xuXHRcdFx0aWYgKChwcmlvcml0eSAmIDEgPT09IDAgfHwgbm90RnVsZmlsbGVkID49IHByaW9yaXR5KSAmJiBPYmplY3Qua2V5cyhfX3dlYnBhY2tfcmVxdWlyZV9fLk8pLmV2ZXJ5KChrZXkpID0+IChfX3dlYnBhY2tfcmVxdWlyZV9fLk9ba2V5XShjaHVua0lkc1tqXSkpKSkge1xuXHRcdFx0XHRjaHVua0lkcy5zcGxpY2Uoai0tLCAxKTtcblx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdGZ1bGZpbGxlZCA9IGZhbHNlO1xuXHRcdFx0XHRpZihwcmlvcml0eSA8IG5vdEZ1bGZpbGxlZCkgbm90RnVsZmlsbGVkID0gcHJpb3JpdHk7XG5cdFx0XHR9XG5cdFx0fVxuXHRcdGlmKGZ1bGZpbGxlZCkge1xuXHRcdFx0ZGVmZXJyZWQuc3BsaWNlKGktLSwgMSlcblx0XHRcdHZhciByID0gZm4oKTtcblx0XHRcdGlmIChyICE9PSB1bmRlZmluZWQpIHJlc3VsdCA9IHI7XG5cdFx0fVxuXHR9XG5cdHJldHVybiByZXN1bHQ7XG59OyIsIi8vIGdldERlZmF1bHRFeHBvcnQgZnVuY3Rpb24gZm9yIGNvbXBhdGliaWxpdHkgd2l0aCBub24taGFybW9ueSBtb2R1bGVzXG5fX3dlYnBhY2tfcmVxdWlyZV9fLm4gPSAobW9kdWxlKSA9PiB7XG5cdHZhciBnZXR0ZXIgPSBtb2R1bGUgJiYgbW9kdWxlLl9fZXNNb2R1bGUgP1xuXHRcdCgpID0+IChtb2R1bGVbJ2RlZmF1bHQnXSkgOlxuXHRcdCgpID0+IChtb2R1bGUpO1xuXHRfX3dlYnBhY2tfcmVxdWlyZV9fLmQoZ2V0dGVyLCB7IGE6IGdldHRlciB9KTtcblx0cmV0dXJuIGdldHRlcjtcbn07IiwiLy8gZGVmaW5lIGdldHRlciBmdW5jdGlvbnMgZm9yIGhhcm1vbnkgZXhwb3J0c1xuX193ZWJwYWNrX3JlcXVpcmVfXy5kID0gKGV4cG9ydHMsIGRlZmluaXRpb24pID0+IHtcblx0Zm9yKHZhciBrZXkgaW4gZGVmaW5pdGlvbikge1xuXHRcdGlmKF9fd2VicGFja19yZXF1aXJlX18ubyhkZWZpbml0aW9uLCBrZXkpICYmICFfX3dlYnBhY2tfcmVxdWlyZV9fLm8oZXhwb3J0cywga2V5KSkge1xuXHRcdFx0T2JqZWN0LmRlZmluZVByb3BlcnR5KGV4cG9ydHMsIGtleSwgeyBlbnVtZXJhYmxlOiB0cnVlLCBnZXQ6IGRlZmluaXRpb25ba2V5XSB9KTtcblx0XHR9XG5cdH1cbn07IiwiX193ZWJwYWNrX3JlcXVpcmVfXy5nID0gKGZ1bmN0aW9uKCkge1xuXHRpZiAodHlwZW9mIGdsb2JhbFRoaXMgPT09ICdvYmplY3QnKSByZXR1cm4gZ2xvYmFsVGhpcztcblx0dHJ5IHtcblx0XHRyZXR1cm4gdGhpcyB8fCBuZXcgRnVuY3Rpb24oJ3JldHVybiB0aGlzJykoKTtcblx0fSBjYXRjaCAoZSkge1xuXHRcdGlmICh0eXBlb2Ygd2luZG93ID09PSAnb2JqZWN0JykgcmV0dXJuIHdpbmRvdztcblx0fVxufSkoKTsiLCJfX3dlYnBhY2tfcmVxdWlyZV9fLm8gPSAob2JqLCBwcm9wKSA9PiAoT2JqZWN0LnByb3RvdHlwZS5oYXNPd25Qcm9wZXJ0eS5jYWxsKG9iaiwgcHJvcCkpIiwiLy8gZGVmaW5lIF9fZXNNb2R1bGUgb24gZXhwb3J0c1xuX193ZWJwYWNrX3JlcXVpcmVfXy5yID0gKGV4cG9ydHMpID0+IHtcblx0aWYodHlwZW9mIFN5bWJvbCAhPT0gJ3VuZGVmaW5lZCcgJiYgU3ltYm9sLnRvU3RyaW5nVGFnKSB7XG5cdFx0T2JqZWN0LmRlZmluZVByb3BlcnR5KGV4cG9ydHMsIFN5bWJvbC50b1N0cmluZ1RhZywgeyB2YWx1ZTogJ01vZHVsZScgfSk7XG5cdH1cblx0T2JqZWN0LmRlZmluZVByb3BlcnR5KGV4cG9ydHMsICdfX2VzTW9kdWxlJywgeyB2YWx1ZTogdHJ1ZSB9KTtcbn07IiwiLy8gbm8gYmFzZVVSSVxuXG4vLyBvYmplY3QgdG8gc3RvcmUgbG9hZGVkIGFuZCBsb2FkaW5nIGNodW5rc1xuLy8gdW5kZWZpbmVkID0gY2h1bmsgbm90IGxvYWRlZCwgbnVsbCA9IGNodW5rIHByZWxvYWRlZC9wcmVmZXRjaGVkXG4vLyBbcmVzb2x2ZSwgcmVqZWN0LCBQcm9taXNlXSA9IGNodW5rIGxvYWRpbmcsIDAgPSBjaHVuayBsb2FkZWRcbnZhciBpbnN0YWxsZWRDaHVua3MgPSB7XG5cdFwic3BlY2lhbF9leHBvcnRcIjogMCxcblx0XCJhc3NldHNfc3R5bGVzX3NwZWNpYWxfZXhwb3J0X3Njc3NcIjogMFxufTtcblxuLy8gbm8gY2h1bmsgb24gZGVtYW5kIGxvYWRpbmdcblxuLy8gbm8gcHJlZmV0Y2hpbmdcblxuLy8gbm8gcHJlbG9hZGVkXG5cbi8vIG5vIEhNUlxuXG4vLyBubyBITVIgbWFuaWZlc3RcblxuX193ZWJwYWNrX3JlcXVpcmVfXy5PLmogPSAoY2h1bmtJZCkgPT4gKGluc3RhbGxlZENodW5rc1tjaHVua0lkXSA9PT0gMCk7XG5cbi8vIGluc3RhbGwgYSBKU09OUCBjYWxsYmFjayBmb3IgY2h1bmsgbG9hZGluZ1xudmFyIHdlYnBhY2tKc29ucENhbGxiYWNrID0gKHBhcmVudENodW5rTG9hZGluZ0Z1bmN0aW9uLCBkYXRhKSA9PiB7XG5cdHZhciBbY2h1bmtJZHMsIG1vcmVNb2R1bGVzLCBydW50aW1lXSA9IGRhdGE7XG5cdC8vIGFkZCBcIm1vcmVNb2R1bGVzXCIgdG8gdGhlIG1vZHVsZXMgb2JqZWN0LFxuXHQvLyB0aGVuIGZsYWcgYWxsIFwiY2h1bmtJZHNcIiBhcyBsb2FkZWQgYW5kIGZpcmUgY2FsbGJhY2tcblx0dmFyIG1vZHVsZUlkLCBjaHVua0lkLCBpID0gMDtcblx0aWYoY2h1bmtJZHMuc29tZSgoaWQpID0+IChpbnN0YWxsZWRDaHVua3NbaWRdICE9PSAwKSkpIHtcblx0XHRmb3IobW9kdWxlSWQgaW4gbW9yZU1vZHVsZXMpIHtcblx0XHRcdGlmKF9fd2VicGFja19yZXF1aXJlX18ubyhtb3JlTW9kdWxlcywgbW9kdWxlSWQpKSB7XG5cdFx0XHRcdF9fd2VicGFja19yZXF1aXJlX18ubVttb2R1bGVJZF0gPSBtb3JlTW9kdWxlc1ttb2R1bGVJZF07XG5cdFx0XHR9XG5cdFx0fVxuXHRcdGlmKHJ1bnRpbWUpIHZhciByZXN1bHQgPSBydW50aW1lKF9fd2VicGFja19yZXF1aXJlX18pO1xuXHR9XG5cdGlmKHBhcmVudENodW5rTG9hZGluZ0Z1bmN0aW9uKSBwYXJlbnRDaHVua0xvYWRpbmdGdW5jdGlvbihkYXRhKTtcblx0Zm9yKDtpIDwgY2h1bmtJZHMubGVuZ3RoOyBpKyspIHtcblx0XHRjaHVua0lkID0gY2h1bmtJZHNbaV07XG5cdFx0aWYoX193ZWJwYWNrX3JlcXVpcmVfXy5vKGluc3RhbGxlZENodW5rcywgY2h1bmtJZCkgJiYgaW5zdGFsbGVkQ2h1bmtzW2NodW5rSWRdKSB7XG5cdFx0XHRpbnN0YWxsZWRDaHVua3NbY2h1bmtJZF1bMF0oKTtcblx0XHR9XG5cdFx0aW5zdGFsbGVkQ2h1bmtzW2NodW5rSWRdID0gMDtcblx0fVxuXHRyZXR1cm4gX193ZWJwYWNrX3JlcXVpcmVfXy5PKHJlc3VsdCk7XG59XG5cbnZhciBjaHVua0xvYWRpbmdHbG9iYWwgPSBnbG9iYWxUaGlzW1wid2VicGFja0NodW5rXCJdID0gZ2xvYmFsVGhpc1tcIndlYnBhY2tDaHVua1wiXSB8fCBbXTtcbmNodW5rTG9hZGluZ0dsb2JhbC5mb3JFYWNoKHdlYnBhY2tKc29ucENhbGxiYWNrLmJpbmQobnVsbCwgMCkpO1xuY2h1bmtMb2FkaW5nR2xvYmFsLnB1c2ggPSB3ZWJwYWNrSnNvbnBDYWxsYmFjay5iaW5kKG51bGwsIGNodW5rTG9hZGluZ0dsb2JhbC5wdXNoLmJpbmQoY2h1bmtMb2FkaW5nR2xvYmFsKSk7IiwiIiwiLy8gc3RhcnR1cFxuLy8gTG9hZCBlbnRyeSBtb2R1bGUgYW5kIHJldHVybiBleHBvcnRzXG4vLyBUaGlzIGVudHJ5IG1vZHVsZSBkZXBlbmRzIG9uIG90aGVyIGxvYWRlZCBjaHVua3MgYW5kIGV4ZWN1dGlvbiBuZWVkIHRvIGJlIGRlbGF5ZWRcbnZhciBfX3dlYnBhY2tfZXhwb3J0c19fID0gX193ZWJwYWNrX3JlcXVpcmVfXy5PKHVuZGVmaW5lZCwgW1widmVuZG9ycy1ub2RlX21vZHVsZXNfanF1ZXJ5X2Rpc3RfanF1ZXJ5X2pzXCIsXCJ2ZW5kb3JzLW5vZGVfbW9kdWxlc19qc3ppcF9kaXN0X2pzemlwX21pbl9qcy1ub2RlX21vZHVsZXNfcGRmbWFrZV9idWlsZF9wZGZtYWtlX2pzLW5vZGVfbW9kdWwtOGQ2MjY3XCIsXCJhc3NldHNfc3R5bGVzX3NwZWNpYWxfZXhwb3J0X3Njc3NcIl0sICgpID0+IChfX3dlYnBhY2tfcmVxdWlyZV9fKFwiLi9hc3NldHMvanMvc3BlY2lhbF9leHBvcnQuanNcIikpKVxuX193ZWJwYWNrX2V4cG9ydHNfXyA9IF9fd2VicGFja19yZXF1aXJlX18uTyhfX3dlYnBhY2tfZXhwb3J0c19fKTtcbiIsIiJdLCJuYW1lcyI6WyIkIiwiSlNaaXAiLCJ3aW5kb3ciLCJwZGZNYWtlIiwicGRmRm9udHMiLCJ2ZnMiLCJEYXRhVGFibGVzIiwiZG9jdW1lbnQiLCJyZWFkeSIsInRhYmxlU2VsZWN0b3IiLCJ0IiwiRGF0YVRhYmxlIiwicGFnaW5nIiwic2VhcmNoaW5nIiwiaW5mbyIsInJlc3BvbnNpdmUiLCJvcmRlcmluZyIsImZuIiwiZGF0YVRhYmxlIiwiQnV0dG9ucyIsImJ1dHRvbnMiLCJleHRlbmQiLCJ0ZXh0IiwiY2xhc3NOYW1lIiwibWVzc2FnZVRvcCIsIm1lc3NhZ2VCb3R0b20iLCJ0aXRsZSIsImZpbGVuYW1lIiwiZm9vdGVyIiwic2hlZXROYW1lIiwiZXhwb3J0T3B0aW9ucyIsImZvcm1hdCIsImJvZHkiLCJkYXRhIiwicm93IiwiY29sdW1uIiwibm9kZSIsImFyciIsInNwbGl0IiwiaW5jbHVkZXMiLCJyZXBsYWNlQWxsIiwiY29udGFpbmVyIiwiYXBwZW5kVG8iXSwic291cmNlUm9vdCI6IiJ9