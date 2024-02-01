/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./assets/js/dashboard.js":
/*!********************************!*\
  !*** ./assets/js/dashboard.js ***!
  \********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var foundation_datepicker__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! foundation-datepicker */ "./node_modules/foundation-datepicker/js/foundation-datepicker.min.js");
/* harmony import */ var foundation_datepicker__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(foundation_datepicker__WEBPACK_IMPORTED_MODULE_1__);


function Display() {
  // Get the checkbox
  let checkBox = document.getElementById("exampleSwitch");

  // If the checkbox is checked, display the output text
  if (checkBox.checked === false) {
    document.getElementById('hour').innerText = "f";
  } else {
    document.getElementById('hour').innerText = "t";
  }
}

/*
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById("showGrid").addEventListener('click',function () {

            const coll = document.getElementsByClassName("showGridtoogle");
            const coll2 = document.getElementsByClassName("showLinetoogle");

            var t = document.getElementById("showGrid");

            if(t.value==="YES"){
                t.value="NO";
                document.getElementById("bton").className = "fa fa-bars";
                changeDisplay(coll,'block');
                changeDisplay(coll2,'none');
            }
            else if(t.value==="NO"){
                t.value="YES";
                document.getElementById("bton").className = "fa fa-pause";
                changeDisplay(coll,'none');
                changeDisplay(coll2,'block');
            }

        } );
    } );
*/

window.onload = function () {
  document.getElementById("clearButton").addEventListener("click", clear);
  document.getElementById("searchText").addEventListener("input", searchPlants);
  //console.log(document.getElementById("searchText"));
};
function changeDisplay(coll, value) {
  for (let i = 0, len = coll.length; i < len; i++) {
    coll[i].style["display"] = value;
  }
}
function clear() {
  document.getElementById("searchText").value = "";
  // Call seach, which should reset the result list
  searchPlants();
}
function searchPlants() {
  let input = document.getElementById("searchText");
  let filter = input.value.toLowerCase();
  let nodes = document.getElementsByClassName('target');
  for (let i = 0; i < nodes.length; i++) {
    if (nodes[i].innerText.toLowerCase().includes(filter)) {
      nodes[i].style.display = "block";
    } else {
      nodes[i].style.display = "none";
    }
  }
}
jquery__WEBPACK_IMPORTED_MODULE_0___default()(".js-submit-onchange").change(function () {
  jquery__WEBPACK_IMPORTED_MODULE_0___default()("#mysubmit").val('yes');
  jquery__WEBPACK_IMPORTED_MODULE_0___default()("#chart-control").submit();
});
jquery__WEBPACK_IMPORTED_MODULE_0___default()(".js-submit-onchange-select").change(function () {
  jquery__WEBPACK_IMPORTED_MODULE_0___default()("#mysubmit").val('select');
  jquery__WEBPACK_IMPORTED_MODULE_0___default()("#chart-control").submit();
});
jquery__WEBPACK_IMPORTED_MODULE_0___default()('#startDate').fdatepicker({
  language: 'en',
  weekStart: '1'
  // endDate: dateString,
});

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
/******/ 			"dashboard": 0
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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["vendors-node_modules_jquery_dist_jquery_js","vendors-node_modules_foundation-datepicker_js_foundation-datepicker_min_js"], () => (__webpack_require__("./assets/js/dashboard.js")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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M0I7V0FDQTtXQUNBO1dBQ0E7V0FDQTtXQUNBO1dBQ0E7V0FDQTs7V0FFQTtXQUNBO1dBQ0E7Ozs7O1VFaERBO1VBQ0E7VUFDQTtVQUNBO1VBQ0EiLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9hc3NldHMvanMvZGFzaGJvYXJkLmpzIiwid2VicGFjazovLy93ZWJwYWNrL2Jvb3RzdHJhcCIsIndlYnBhY2s6Ly8vd2VicGFjay9ydW50aW1lL2NodW5rIGxvYWRlZCIsIndlYnBhY2s6Ly8vd2VicGFjay9ydW50aW1lL2NvbXBhdCBnZXQgZGVmYXVsdCBleHBvcnQiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svcnVudGltZS9kZWZpbmUgcHJvcGVydHkgZ2V0dGVycyIsIndlYnBhY2s6Ly8vd2VicGFjay9ydW50aW1lL2hhc093blByb3BlcnR5IHNob3J0aGFuZCIsIndlYnBhY2s6Ly8vd2VicGFjay9ydW50aW1lL21ha2UgbmFtZXNwYWNlIG9iamVjdCIsIndlYnBhY2s6Ly8vd2VicGFjay9ydW50aW1lL2pzb25wIGNodW5rIGxvYWRpbmciLCJ3ZWJwYWNrOi8vL3dlYnBhY2svYmVmb3JlLXN0YXJ0dXAiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svc3RhcnR1cCIsIndlYnBhY2s6Ly8vd2VicGFjay9hZnRlci1zdGFydHVwIl0sInNvdXJjZXNDb250ZW50IjpbImltcG9ydCAkIGZyb20gXCJqcXVlcnlcIjtcbmltcG9ydCBmZGF0ZXBpY2tlciBmcm9tICdmb3VuZGF0aW9uLWRhdGVwaWNrZXInO1xuXG5mdW5jdGlvbiBEaXNwbGF5KCkge1xuICAgIC8vIEdldCB0aGUgY2hlY2tib3hcbiAgICBsZXQgY2hlY2tCb3ggPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZChcImV4YW1wbGVTd2l0Y2hcIik7XG5cbiAgICAvLyBJZiB0aGUgY2hlY2tib3ggaXMgY2hlY2tlZCwgZGlzcGxheSB0aGUgb3V0cHV0IHRleHRcbiAgICBpZiAoY2hlY2tCb3guY2hlY2tlZCA9PT0gZmFsc2UpIHtcbiAgICAgICAgZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2hvdXInKS5pbm5lclRleHQgPSBcImZcIjtcbiAgICB9IGVsc2Uge1xuICAgICAgICBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnaG91cicpLmlubmVyVGV4dCA9IFwidFwiO1xuICAgIH1cbn1cblxuXG4vKlxuICAgIGRvY3VtZW50LmFkZEV2ZW50TGlzdGVuZXIoJ0RPTUNvbnRlbnRMb2FkZWQnLCBmdW5jdGlvbigpIHtcbiAgICAgICAgZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoXCJzaG93R3JpZFwiKS5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsZnVuY3Rpb24gKCkge1xuXG4gICAgICAgICAgICBjb25zdCBjb2xsID0gZG9jdW1lbnQuZ2V0RWxlbWVudHNCeUNsYXNzTmFtZShcInNob3dHcmlkdG9vZ2xlXCIpO1xuICAgICAgICAgICAgY29uc3QgY29sbDIgPSBkb2N1bWVudC5nZXRFbGVtZW50c0J5Q2xhc3NOYW1lKFwic2hvd0xpbmV0b29nbGVcIik7XG5cbiAgICAgICAgICAgIHZhciB0ID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoXCJzaG93R3JpZFwiKTtcblxuICAgICAgICAgICAgaWYodC52YWx1ZT09PVwiWUVTXCIpe1xuICAgICAgICAgICAgICAgIHQudmFsdWU9XCJOT1wiO1xuICAgICAgICAgICAgICAgIGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKFwiYnRvblwiKS5jbGFzc05hbWUgPSBcImZhIGZhLWJhcnNcIjtcbiAgICAgICAgICAgICAgICBjaGFuZ2VEaXNwbGF5KGNvbGwsJ2Jsb2NrJyk7XG4gICAgICAgICAgICAgICAgY2hhbmdlRGlzcGxheShjb2xsMiwnbm9uZScpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgZWxzZSBpZih0LnZhbHVlPT09XCJOT1wiKXtcbiAgICAgICAgICAgICAgICB0LnZhbHVlPVwiWUVTXCI7XG4gICAgICAgICAgICAgICAgZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoXCJidG9uXCIpLmNsYXNzTmFtZSA9IFwiZmEgZmEtcGF1c2VcIjtcbiAgICAgICAgICAgICAgICBjaGFuZ2VEaXNwbGF5KGNvbGwsJ25vbmUnKTtcbiAgICAgICAgICAgICAgICBjaGFuZ2VEaXNwbGF5KGNvbGwyLCdibG9jaycpO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgIH0gKTtcbiAgICB9ICk7XG4qL1xuXG53aW5kb3cub25sb2FkID0gZnVuY3Rpb24oKSB7XG4gICAgZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoXCJjbGVhckJ1dHRvblwiKS5hZGRFdmVudExpc3RlbmVyKFwiY2xpY2tcIiwgY2xlYXIpO1xuICAgIGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKFwic2VhcmNoVGV4dFwiKS5hZGRFdmVudExpc3RlbmVyKFwiaW5wdXRcIiwgc2VhcmNoUGxhbnRzKTtcbiAgICAvL2NvbnNvbGUubG9nKGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKFwic2VhcmNoVGV4dFwiKSk7XG59XG5cbmZ1bmN0aW9uIGNoYW5nZURpc3BsYXkoY29sbCwgdmFsdWUpe1xuICAgIGZvcihsZXQgaT0wLCBsZW49Y29sbC5sZW5ndGg7IGk8bGVuOyBpKyspIHtcbiAgICAgICAgY29sbFtpXS5zdHlsZVtcImRpc3BsYXlcIl0gPSB2YWx1ZTtcbiAgICB9XG59XG5cbmZ1bmN0aW9uIGNsZWFyKCl7XG4gICAgZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoXCJzZWFyY2hUZXh0XCIpLnZhbHVlID0gXCJcIjtcbiAgICAvLyBDYWxsIHNlYWNoLCB3aGljaCBzaG91bGQgcmVzZXQgdGhlIHJlc3VsdCBsaXN0XG4gICAgc2VhcmNoUGxhbnRzKCk7XG59XG5cbmZ1bmN0aW9uIHNlYXJjaFBsYW50cygpIHtcbiAgICBsZXQgaW5wdXQgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZChcInNlYXJjaFRleHRcIik7XG4gICAgbGV0IGZpbHRlciA9IGlucHV0LnZhbHVlLnRvTG93ZXJDYXNlKCk7XG4gICAgbGV0IG5vZGVzID0gZG9jdW1lbnQuZ2V0RWxlbWVudHNCeUNsYXNzTmFtZSgndGFyZ2V0Jyk7XG5cbiAgICBmb3IgKGxldCBpID0gMDsgaSA8IG5vZGVzLmxlbmd0aDsgaSsrKSB7XG4gICAgICAgIGlmIChub2Rlc1tpXS5pbm5lclRleHQudG9Mb3dlckNhc2UoKS5pbmNsdWRlcyhmaWx0ZXIpKSB7XG4gICAgICAgICAgICBub2Rlc1tpXS5zdHlsZS5kaXNwbGF5ID0gXCJibG9ja1wiO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgbm9kZXNbaV0uc3R5bGUuZGlzcGxheSA9IFwibm9uZVwiO1xuICAgICAgICB9XG4gICAgfVxufVxuXG4kKFwiLmpzLXN1Ym1pdC1vbmNoYW5nZVwiKS5jaGFuZ2UoZnVuY3Rpb24gKCkge1xuICAgICQoXCIjbXlzdWJtaXRcIikudmFsKCd5ZXMnKTtcbiAgICAkKFwiI2NoYXJ0LWNvbnRyb2xcIikuc3VibWl0KCk7XG59KTtcblxuJChcIi5qcy1zdWJtaXQtb25jaGFuZ2Utc2VsZWN0XCIpLmNoYW5nZShmdW5jdGlvbiAoKSB7XG4gICAgJChcIiNteXN1Ym1pdFwiKS52YWwoJ3NlbGVjdCcpO1xuICAgICQoXCIjY2hhcnQtY29udHJvbFwiKS5zdWJtaXQoKTtcbn0pO1xuXG4kKCcjc3RhcnREYXRlJykuZmRhdGVwaWNrZXIoe1xuICAgIGxhbmd1YWdlOiAnZW4nLFxuICAgIHdlZWtTdGFydDogJzEnLFxuICAgIC8vIGVuZERhdGU6IGRhdGVTdHJpbmcsXG59KTtcblxuXG5cbiIsIi8vIFRoZSBtb2R1bGUgY2FjaGVcbnZhciBfX3dlYnBhY2tfbW9kdWxlX2NhY2hlX18gPSB7fTtcblxuLy8gVGhlIHJlcXVpcmUgZnVuY3Rpb25cbmZ1bmN0aW9uIF9fd2VicGFja19yZXF1aXJlX18obW9kdWxlSWQpIHtcblx0Ly8gQ2hlY2sgaWYgbW9kdWxlIGlzIGluIGNhY2hlXG5cdHZhciBjYWNoZWRNb2R1bGUgPSBfX3dlYnBhY2tfbW9kdWxlX2NhY2hlX19bbW9kdWxlSWRdO1xuXHRpZiAoY2FjaGVkTW9kdWxlICE9PSB1bmRlZmluZWQpIHtcblx0XHRyZXR1cm4gY2FjaGVkTW9kdWxlLmV4cG9ydHM7XG5cdH1cblx0Ly8gQ3JlYXRlIGEgbmV3IG1vZHVsZSAoYW5kIHB1dCBpdCBpbnRvIHRoZSBjYWNoZSlcblx0dmFyIG1vZHVsZSA9IF9fd2VicGFja19tb2R1bGVfY2FjaGVfX1ttb2R1bGVJZF0gPSB7XG5cdFx0Ly8gbm8gbW9kdWxlLmlkIG5lZWRlZFxuXHRcdC8vIG5vIG1vZHVsZS5sb2FkZWQgbmVlZGVkXG5cdFx0ZXhwb3J0czoge31cblx0fTtcblxuXHQvLyBFeGVjdXRlIHRoZSBtb2R1bGUgZnVuY3Rpb25cblx0X193ZWJwYWNrX21vZHVsZXNfX1ttb2R1bGVJZF0uY2FsbChtb2R1bGUuZXhwb3J0cywgbW9kdWxlLCBtb2R1bGUuZXhwb3J0cywgX193ZWJwYWNrX3JlcXVpcmVfXyk7XG5cblx0Ly8gUmV0dXJuIHRoZSBleHBvcnRzIG9mIHRoZSBtb2R1bGVcblx0cmV0dXJuIG1vZHVsZS5leHBvcnRzO1xufVxuXG4vLyBleHBvc2UgdGhlIG1vZHVsZXMgb2JqZWN0IChfX3dlYnBhY2tfbW9kdWxlc19fKVxuX193ZWJwYWNrX3JlcXVpcmVfXy5tID0gX193ZWJwYWNrX21vZHVsZXNfXztcblxuIiwidmFyIGRlZmVycmVkID0gW107XG5fX3dlYnBhY2tfcmVxdWlyZV9fLk8gPSAocmVzdWx0LCBjaHVua0lkcywgZm4sIHByaW9yaXR5KSA9PiB7XG5cdGlmKGNodW5rSWRzKSB7XG5cdFx0cHJpb3JpdHkgPSBwcmlvcml0eSB8fCAwO1xuXHRcdGZvcih2YXIgaSA9IGRlZmVycmVkLmxlbmd0aDsgaSA+IDAgJiYgZGVmZXJyZWRbaSAtIDFdWzJdID4gcHJpb3JpdHk7IGktLSkgZGVmZXJyZWRbaV0gPSBkZWZlcnJlZFtpIC0gMV07XG5cdFx0ZGVmZXJyZWRbaV0gPSBbY2h1bmtJZHMsIGZuLCBwcmlvcml0eV07XG5cdFx0cmV0dXJuO1xuXHR9XG5cdHZhciBub3RGdWxmaWxsZWQgPSBJbmZpbml0eTtcblx0Zm9yICh2YXIgaSA9IDA7IGkgPCBkZWZlcnJlZC5sZW5ndGg7IGkrKykge1xuXHRcdHZhciBbY2h1bmtJZHMsIGZuLCBwcmlvcml0eV0gPSBkZWZlcnJlZFtpXTtcblx0XHR2YXIgZnVsZmlsbGVkID0gdHJ1ZTtcblx0XHRmb3IgKHZhciBqID0gMDsgaiA8IGNodW5rSWRzLmxlbmd0aDsgaisrKSB7XG5cdFx0XHRpZiAoKHByaW9yaXR5ICYgMSA9PT0gMCB8fCBub3RGdWxmaWxsZWQgPj0gcHJpb3JpdHkpICYmIE9iamVjdC5rZXlzKF9fd2VicGFja19yZXF1aXJlX18uTykuZXZlcnkoKGtleSkgPT4gKF9fd2VicGFja19yZXF1aXJlX18uT1trZXldKGNodW5rSWRzW2pdKSkpKSB7XG5cdFx0XHRcdGNodW5rSWRzLnNwbGljZShqLS0sIDEpO1xuXHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0ZnVsZmlsbGVkID0gZmFsc2U7XG5cdFx0XHRcdGlmKHByaW9yaXR5IDwgbm90RnVsZmlsbGVkKSBub3RGdWxmaWxsZWQgPSBwcmlvcml0eTtcblx0XHRcdH1cblx0XHR9XG5cdFx0aWYoZnVsZmlsbGVkKSB7XG5cdFx0XHRkZWZlcnJlZC5zcGxpY2UoaS0tLCAxKVxuXHRcdFx0dmFyIHIgPSBmbigpO1xuXHRcdFx0aWYgKHIgIT09IHVuZGVmaW5lZCkgcmVzdWx0ID0gcjtcblx0XHR9XG5cdH1cblx0cmV0dXJuIHJlc3VsdDtcbn07IiwiLy8gZ2V0RGVmYXVsdEV4cG9ydCBmdW5jdGlvbiBmb3IgY29tcGF0aWJpbGl0eSB3aXRoIG5vbi1oYXJtb255IG1vZHVsZXNcbl9fd2VicGFja19yZXF1aXJlX18ubiA9IChtb2R1bGUpID0+IHtcblx0dmFyIGdldHRlciA9IG1vZHVsZSAmJiBtb2R1bGUuX19lc01vZHVsZSA/XG5cdFx0KCkgPT4gKG1vZHVsZVsnZGVmYXVsdCddKSA6XG5cdFx0KCkgPT4gKG1vZHVsZSk7XG5cdF9fd2VicGFja19yZXF1aXJlX18uZChnZXR0ZXIsIHsgYTogZ2V0dGVyIH0pO1xuXHRyZXR1cm4gZ2V0dGVyO1xufTsiLCIvLyBkZWZpbmUgZ2V0dGVyIGZ1bmN0aW9ucyBmb3IgaGFybW9ueSBleHBvcnRzXG5fX3dlYnBhY2tfcmVxdWlyZV9fLmQgPSAoZXhwb3J0cywgZGVmaW5pdGlvbikgPT4ge1xuXHRmb3IodmFyIGtleSBpbiBkZWZpbml0aW9uKSB7XG5cdFx0aWYoX193ZWJwYWNrX3JlcXVpcmVfXy5vKGRlZmluaXRpb24sIGtleSkgJiYgIV9fd2VicGFja19yZXF1aXJlX18ubyhleHBvcnRzLCBrZXkpKSB7XG5cdFx0XHRPYmplY3QuZGVmaW5lUHJvcGVydHkoZXhwb3J0cywga2V5LCB7IGVudW1lcmFibGU6IHRydWUsIGdldDogZGVmaW5pdGlvbltrZXldIH0pO1xuXHRcdH1cblx0fVxufTsiLCJfX3dlYnBhY2tfcmVxdWlyZV9fLm8gPSAob2JqLCBwcm9wKSA9PiAoT2JqZWN0LnByb3RvdHlwZS5oYXNPd25Qcm9wZXJ0eS5jYWxsKG9iaiwgcHJvcCkpIiwiLy8gZGVmaW5lIF9fZXNNb2R1bGUgb24gZXhwb3J0c1xuX193ZWJwYWNrX3JlcXVpcmVfXy5yID0gKGV4cG9ydHMpID0+IHtcblx0aWYodHlwZW9mIFN5bWJvbCAhPT0gJ3VuZGVmaW5lZCcgJiYgU3ltYm9sLnRvU3RyaW5nVGFnKSB7XG5cdFx0T2JqZWN0LmRlZmluZVByb3BlcnR5KGV4cG9ydHMsIFN5bWJvbC50b1N0cmluZ1RhZywgeyB2YWx1ZTogJ01vZHVsZScgfSk7XG5cdH1cblx0T2JqZWN0LmRlZmluZVByb3BlcnR5KGV4cG9ydHMsICdfX2VzTW9kdWxlJywgeyB2YWx1ZTogdHJ1ZSB9KTtcbn07IiwiLy8gbm8gYmFzZVVSSVxuXG4vLyBvYmplY3QgdG8gc3RvcmUgbG9hZGVkIGFuZCBsb2FkaW5nIGNodW5rc1xuLy8gdW5kZWZpbmVkID0gY2h1bmsgbm90IGxvYWRlZCwgbnVsbCA9IGNodW5rIHByZWxvYWRlZC9wcmVmZXRjaGVkXG4vLyBbcmVzb2x2ZSwgcmVqZWN0LCBQcm9taXNlXSA9IGNodW5rIGxvYWRpbmcsIDAgPSBjaHVuayBsb2FkZWRcbnZhciBpbnN0YWxsZWRDaHVua3MgPSB7XG5cdFwiZGFzaGJvYXJkXCI6IDBcbn07XG5cbi8vIG5vIGNodW5rIG9uIGRlbWFuZCBsb2FkaW5nXG5cbi8vIG5vIHByZWZldGNoaW5nXG5cbi8vIG5vIHByZWxvYWRlZFxuXG4vLyBubyBITVJcblxuLy8gbm8gSE1SIG1hbmlmZXN0XG5cbl9fd2VicGFja19yZXF1aXJlX18uTy5qID0gKGNodW5rSWQpID0+IChpbnN0YWxsZWRDaHVua3NbY2h1bmtJZF0gPT09IDApO1xuXG4vLyBpbnN0YWxsIGEgSlNPTlAgY2FsbGJhY2sgZm9yIGNodW5rIGxvYWRpbmdcbnZhciB3ZWJwYWNrSnNvbnBDYWxsYmFjayA9IChwYXJlbnRDaHVua0xvYWRpbmdGdW5jdGlvbiwgZGF0YSkgPT4ge1xuXHR2YXIgW2NodW5rSWRzLCBtb3JlTW9kdWxlcywgcnVudGltZV0gPSBkYXRhO1xuXHQvLyBhZGQgXCJtb3JlTW9kdWxlc1wiIHRvIHRoZSBtb2R1bGVzIG9iamVjdCxcblx0Ly8gdGhlbiBmbGFnIGFsbCBcImNodW5rSWRzXCIgYXMgbG9hZGVkIGFuZCBmaXJlIGNhbGxiYWNrXG5cdHZhciBtb2R1bGVJZCwgY2h1bmtJZCwgaSA9IDA7XG5cdGlmKGNodW5rSWRzLnNvbWUoKGlkKSA9PiAoaW5zdGFsbGVkQ2h1bmtzW2lkXSAhPT0gMCkpKSB7XG5cdFx0Zm9yKG1vZHVsZUlkIGluIG1vcmVNb2R1bGVzKSB7XG5cdFx0XHRpZihfX3dlYnBhY2tfcmVxdWlyZV9fLm8obW9yZU1vZHVsZXMsIG1vZHVsZUlkKSkge1xuXHRcdFx0XHRfX3dlYnBhY2tfcmVxdWlyZV9fLm1bbW9kdWxlSWRdID0gbW9yZU1vZHVsZXNbbW9kdWxlSWRdO1xuXHRcdFx0fVxuXHRcdH1cblx0XHRpZihydW50aW1lKSB2YXIgcmVzdWx0ID0gcnVudGltZShfX3dlYnBhY2tfcmVxdWlyZV9fKTtcblx0fVxuXHRpZihwYXJlbnRDaHVua0xvYWRpbmdGdW5jdGlvbikgcGFyZW50Q2h1bmtMb2FkaW5nRnVuY3Rpb24oZGF0YSk7XG5cdGZvcig7aSA8IGNodW5rSWRzLmxlbmd0aDsgaSsrKSB7XG5cdFx0Y2h1bmtJZCA9IGNodW5rSWRzW2ldO1xuXHRcdGlmKF9fd2VicGFja19yZXF1aXJlX18ubyhpbnN0YWxsZWRDaHVua3MsIGNodW5rSWQpICYmIGluc3RhbGxlZENodW5rc1tjaHVua0lkXSkge1xuXHRcdFx0aW5zdGFsbGVkQ2h1bmtzW2NodW5rSWRdWzBdKCk7XG5cdFx0fVxuXHRcdGluc3RhbGxlZENodW5rc1tjaHVua0lkXSA9IDA7XG5cdH1cblx0cmV0dXJuIF9fd2VicGFja19yZXF1aXJlX18uTyhyZXN1bHQpO1xufVxuXG52YXIgY2h1bmtMb2FkaW5nR2xvYmFsID0gZ2xvYmFsVGhpc1tcIndlYnBhY2tDaHVua1wiXSA9IGdsb2JhbFRoaXNbXCJ3ZWJwYWNrQ2h1bmtcIl0gfHwgW107XG5jaHVua0xvYWRpbmdHbG9iYWwuZm9yRWFjaCh3ZWJwYWNrSnNvbnBDYWxsYmFjay5iaW5kKG51bGwsIDApKTtcbmNodW5rTG9hZGluZ0dsb2JhbC5wdXNoID0gd2VicGFja0pzb25wQ2FsbGJhY2suYmluZChudWxsLCBjaHVua0xvYWRpbmdHbG9iYWwucHVzaC5iaW5kKGNodW5rTG9hZGluZ0dsb2JhbCkpOyIsIiIsIi8vIHN0YXJ0dXBcbi8vIExvYWQgZW50cnkgbW9kdWxlIGFuZCByZXR1cm4gZXhwb3J0c1xuLy8gVGhpcyBlbnRyeSBtb2R1bGUgZGVwZW5kcyBvbiBvdGhlciBsb2FkZWQgY2h1bmtzIGFuZCBleGVjdXRpb24gbmVlZCB0byBiZSBkZWxheWVkXG52YXIgX193ZWJwYWNrX2V4cG9ydHNfXyA9IF9fd2VicGFja19yZXF1aXJlX18uTyh1bmRlZmluZWQsIFtcInZlbmRvcnMtbm9kZV9tb2R1bGVzX2pxdWVyeV9kaXN0X2pxdWVyeV9qc1wiLFwidmVuZG9ycy1ub2RlX21vZHVsZXNfZm91bmRhdGlvbi1kYXRlcGlja2VyX2pzX2ZvdW5kYXRpb24tZGF0ZXBpY2tlcl9taW5fanNcIl0sICgpID0+IChfX3dlYnBhY2tfcmVxdWlyZV9fKFwiLi9hc3NldHMvanMvZGFzaGJvYXJkLmpzXCIpKSlcbl9fd2VicGFja19leHBvcnRzX18gPSBfX3dlYnBhY2tfcmVxdWlyZV9fLk8oX193ZWJwYWNrX2V4cG9ydHNfXyk7XG4iLCIiXSwibmFtZXMiOlsiJCIsImZkYXRlcGlja2VyIiwiRGlzcGxheSIsImNoZWNrQm94IiwiZG9jdW1lbnQiLCJnZXRFbGVtZW50QnlJZCIsImNoZWNrZWQiLCJpbm5lclRleHQiLCJ3aW5kb3ciLCJvbmxvYWQiLCJhZGRFdmVudExpc3RlbmVyIiwiY2xlYXIiLCJzZWFyY2hQbGFudHMiLCJjaGFuZ2VEaXNwbGF5IiwiY29sbCIsInZhbHVlIiwiaSIsImxlbiIsImxlbmd0aCIsInN0eWxlIiwiaW5wdXQiLCJmaWx0ZXIiLCJ0b0xvd2VyQ2FzZSIsIm5vZGVzIiwiZ2V0RWxlbWVudHNCeUNsYXNzTmFtZSIsImluY2x1ZGVzIiwiZGlzcGxheSIsImNoYW5nZSIsInZhbCIsInN1Ym1pdCIsImxhbmd1YWdlIiwid2Vla1N0YXJ0Il0sInNvdXJjZVJvb3QiOiIifQ==