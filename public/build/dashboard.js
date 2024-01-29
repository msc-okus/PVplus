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
  console.log(document.getElementById("searchText"));
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
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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O1dBQ0E7V0FDQTtXQUNBO1dBQ0E7V0FDQTtXQUNBO1dBQ0E7V0FDQTtXQUNBLE1BQU0scUJBQXFCO1dBQzNCO1dBQ0E7V0FDQTtXQUNBO1dBQ0E7V0FDQTtXQUNBO1dBQ0E7O1dBRUE7V0FDQTtXQUNBOzs7OztVRWhEQTtVQUNBO1VBQ0E7VUFDQTtVQUNBIiwic291cmNlcyI6WyJ3ZWJwYWNrOi8vLy4vYXNzZXRzL2pzL2Rhc2hib2FyZC5qcyIsIndlYnBhY2s6Ly8vd2VicGFjay9ib290c3RyYXAiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svcnVudGltZS9jaHVuayBsb2FkZWQiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svcnVudGltZS9jb21wYXQgZ2V0IGRlZmF1bHQgZXhwb3J0Iiwid2VicGFjazovLy93ZWJwYWNrL3J1bnRpbWUvZGVmaW5lIHByb3BlcnR5IGdldHRlcnMiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svcnVudGltZS9oYXNPd25Qcm9wZXJ0eSBzaG9ydGhhbmQiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svcnVudGltZS9tYWtlIG5hbWVzcGFjZSBvYmplY3QiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svcnVudGltZS9qc29ucCBjaHVuayBsb2FkaW5nIiwid2VicGFjazovLy93ZWJwYWNrL2JlZm9yZS1zdGFydHVwIiwid2VicGFjazovLy93ZWJwYWNrL3N0YXJ0dXAiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svYWZ0ZXItc3RhcnR1cCJdLCJzb3VyY2VzQ29udGVudCI6WyJpbXBvcnQgJCBmcm9tIFwianF1ZXJ5XCI7XG5pbXBvcnQgZmRhdGVwaWNrZXIgZnJvbSAnZm91bmRhdGlvbi1kYXRlcGlja2VyJztcblxuZnVuY3Rpb24gRGlzcGxheSgpIHtcbiAgICAvLyBHZXQgdGhlIGNoZWNrYm94XG4gICAgbGV0IGNoZWNrQm94ID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoXCJleGFtcGxlU3dpdGNoXCIpO1xuXG4gICAgLy8gSWYgdGhlIGNoZWNrYm94IGlzIGNoZWNrZWQsIGRpc3BsYXkgdGhlIG91dHB1dCB0ZXh0XG4gICAgaWYgKGNoZWNrQm94LmNoZWNrZWQgPT09IGZhbHNlKSB7XG4gICAgICAgIGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdob3VyJykuaW5uZXJUZXh0ID0gXCJmXCI7XG4gICAgfSBlbHNlIHtcbiAgICAgICAgZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2hvdXInKS5pbm5lclRleHQgPSBcInRcIjtcbiAgICB9XG59XG5cblxuLypcbiAgICBkb2N1bWVudC5hZGRFdmVudExpc3RlbmVyKCdET01Db250ZW50TG9hZGVkJywgZnVuY3Rpb24oKSB7XG4gICAgICAgIGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKFwic2hvd0dyaWRcIikuYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLGZ1bmN0aW9uICgpIHtcblxuICAgICAgICAgICAgY29uc3QgY29sbCA9IGRvY3VtZW50LmdldEVsZW1lbnRzQnlDbGFzc05hbWUoXCJzaG93R3JpZHRvb2dsZVwiKTtcbiAgICAgICAgICAgIGNvbnN0IGNvbGwyID0gZG9jdW1lbnQuZ2V0RWxlbWVudHNCeUNsYXNzTmFtZShcInNob3dMaW5ldG9vZ2xlXCIpO1xuXG4gICAgICAgICAgICB2YXIgdCA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKFwic2hvd0dyaWRcIik7XG5cbiAgICAgICAgICAgIGlmKHQudmFsdWU9PT1cIllFU1wiKXtcbiAgICAgICAgICAgICAgICB0LnZhbHVlPVwiTk9cIjtcbiAgICAgICAgICAgICAgICBkb2N1bWVudC5nZXRFbGVtZW50QnlJZChcImJ0b25cIikuY2xhc3NOYW1lID0gXCJmYSBmYS1iYXJzXCI7XG4gICAgICAgICAgICAgICAgY2hhbmdlRGlzcGxheShjb2xsLCdibG9jaycpO1xuICAgICAgICAgICAgICAgIGNoYW5nZURpc3BsYXkoY29sbDIsJ25vbmUnKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGVsc2UgaWYodC52YWx1ZT09PVwiTk9cIil7XG4gICAgICAgICAgICAgICAgdC52YWx1ZT1cIllFU1wiO1xuICAgICAgICAgICAgICAgIGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKFwiYnRvblwiKS5jbGFzc05hbWUgPSBcImZhIGZhLXBhdXNlXCI7XG4gICAgICAgICAgICAgICAgY2hhbmdlRGlzcGxheShjb2xsLCdub25lJyk7XG4gICAgICAgICAgICAgICAgY2hhbmdlRGlzcGxheShjb2xsMiwnYmxvY2snKTtcbiAgICAgICAgICAgIH1cblxuICAgICAgICB9ICk7XG4gICAgfSApO1xuKi9cblxud2luZG93Lm9ubG9hZCA9IGZ1bmN0aW9uKCkge1xuICAgIGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKFwiY2xlYXJCdXR0b25cIikuYWRkRXZlbnRMaXN0ZW5lcihcImNsaWNrXCIsIGNsZWFyKTtcbiAgICBkb2N1bWVudC5nZXRFbGVtZW50QnlJZChcInNlYXJjaFRleHRcIikuYWRkRXZlbnRMaXN0ZW5lcihcImlucHV0XCIsIHNlYXJjaFBsYW50cyk7XG4gICAgY29uc29sZS5sb2coZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoXCJzZWFyY2hUZXh0XCIpKTtcbn1cblxuZnVuY3Rpb24gY2hhbmdlRGlzcGxheShjb2xsLCB2YWx1ZSl7XG4gICAgZm9yKGxldCBpPTAsIGxlbj1jb2xsLmxlbmd0aDsgaTxsZW47IGkrKykge1xuICAgICAgICBjb2xsW2ldLnN0eWxlW1wiZGlzcGxheVwiXSA9IHZhbHVlO1xuICAgIH1cbn1cblxuZnVuY3Rpb24gY2xlYXIoKXtcbiAgICBkb2N1bWVudC5nZXRFbGVtZW50QnlJZChcInNlYXJjaFRleHRcIikudmFsdWUgPSBcIlwiO1xuICAgIC8vIENhbGwgc2VhY2gsIHdoaWNoIHNob3VsZCByZXNldCB0aGUgcmVzdWx0IGxpc3RcbiAgICBzZWFyY2hQbGFudHMoKTtcbn1cblxuZnVuY3Rpb24gc2VhcmNoUGxhbnRzKCkge1xuICAgIGxldCBpbnB1dCA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKFwic2VhcmNoVGV4dFwiKTtcbiAgICBsZXQgZmlsdGVyID0gaW5wdXQudmFsdWUudG9Mb3dlckNhc2UoKTtcbiAgICBsZXQgbm9kZXMgPSBkb2N1bWVudC5nZXRFbGVtZW50c0J5Q2xhc3NOYW1lKCd0YXJnZXQnKTtcblxuICAgIGZvciAobGV0IGkgPSAwOyBpIDwgbm9kZXMubGVuZ3RoOyBpKyspIHtcbiAgICAgICAgaWYgKG5vZGVzW2ldLmlubmVyVGV4dC50b0xvd2VyQ2FzZSgpLmluY2x1ZGVzKGZpbHRlcikpIHtcbiAgICAgICAgICAgIG5vZGVzW2ldLnN0eWxlLmRpc3BsYXkgPSBcImJsb2NrXCI7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICBub2Rlc1tpXS5zdHlsZS5kaXNwbGF5ID0gXCJub25lXCI7XG4gICAgICAgIH1cbiAgICB9XG59XG5cbiQoXCIuanMtc3VibWl0LW9uY2hhbmdlXCIpLmNoYW5nZShmdW5jdGlvbiAoKSB7XG4gICAgJChcIiNteXN1Ym1pdFwiKS52YWwoJ3llcycpO1xuICAgICQoXCIjY2hhcnQtY29udHJvbFwiKS5zdWJtaXQoKTtcbn0pO1xuXG4kKFwiLmpzLXN1Ym1pdC1vbmNoYW5nZS1zZWxlY3RcIikuY2hhbmdlKGZ1bmN0aW9uICgpIHtcbiAgICAkKFwiI215c3VibWl0XCIpLnZhbCgnc2VsZWN0Jyk7XG4gICAgJChcIiNjaGFydC1jb250cm9sXCIpLnN1Ym1pdCgpO1xufSk7XG5cbiQoJyNzdGFydERhdGUnKS5mZGF0ZXBpY2tlcih7XG4gICAgbGFuZ3VhZ2U6ICdlbicsXG4gICAgd2Vla1N0YXJ0OiAnMScsXG4gICAgLy8gZW5kRGF0ZTogZGF0ZVN0cmluZyxcbn0pO1xuXG5cblxuIiwiLy8gVGhlIG1vZHVsZSBjYWNoZVxudmFyIF9fd2VicGFja19tb2R1bGVfY2FjaGVfXyA9IHt9O1xuXG4vLyBUaGUgcmVxdWlyZSBmdW5jdGlvblxuZnVuY3Rpb24gX193ZWJwYWNrX3JlcXVpcmVfXyhtb2R1bGVJZCkge1xuXHQvLyBDaGVjayBpZiBtb2R1bGUgaXMgaW4gY2FjaGVcblx0dmFyIGNhY2hlZE1vZHVsZSA9IF9fd2VicGFja19tb2R1bGVfY2FjaGVfX1ttb2R1bGVJZF07XG5cdGlmIChjYWNoZWRNb2R1bGUgIT09IHVuZGVmaW5lZCkge1xuXHRcdHJldHVybiBjYWNoZWRNb2R1bGUuZXhwb3J0cztcblx0fVxuXHQvLyBDcmVhdGUgYSBuZXcgbW9kdWxlIChhbmQgcHV0IGl0IGludG8gdGhlIGNhY2hlKVxuXHR2YXIgbW9kdWxlID0gX193ZWJwYWNrX21vZHVsZV9jYWNoZV9fW21vZHVsZUlkXSA9IHtcblx0XHQvLyBubyBtb2R1bGUuaWQgbmVlZGVkXG5cdFx0Ly8gbm8gbW9kdWxlLmxvYWRlZCBuZWVkZWRcblx0XHRleHBvcnRzOiB7fVxuXHR9O1xuXG5cdC8vIEV4ZWN1dGUgdGhlIG1vZHVsZSBmdW5jdGlvblxuXHRfX3dlYnBhY2tfbW9kdWxlc19fW21vZHVsZUlkXS5jYWxsKG1vZHVsZS5leHBvcnRzLCBtb2R1bGUsIG1vZHVsZS5leHBvcnRzLCBfX3dlYnBhY2tfcmVxdWlyZV9fKTtcblxuXHQvLyBSZXR1cm4gdGhlIGV4cG9ydHMgb2YgdGhlIG1vZHVsZVxuXHRyZXR1cm4gbW9kdWxlLmV4cG9ydHM7XG59XG5cbi8vIGV4cG9zZSB0aGUgbW9kdWxlcyBvYmplY3QgKF9fd2VicGFja19tb2R1bGVzX18pXG5fX3dlYnBhY2tfcmVxdWlyZV9fLm0gPSBfX3dlYnBhY2tfbW9kdWxlc19fO1xuXG4iLCJ2YXIgZGVmZXJyZWQgPSBbXTtcbl9fd2VicGFja19yZXF1aXJlX18uTyA9IChyZXN1bHQsIGNodW5rSWRzLCBmbiwgcHJpb3JpdHkpID0+IHtcblx0aWYoY2h1bmtJZHMpIHtcblx0XHRwcmlvcml0eSA9IHByaW9yaXR5IHx8IDA7XG5cdFx0Zm9yKHZhciBpID0gZGVmZXJyZWQubGVuZ3RoOyBpID4gMCAmJiBkZWZlcnJlZFtpIC0gMV1bMl0gPiBwcmlvcml0eTsgaS0tKSBkZWZlcnJlZFtpXSA9IGRlZmVycmVkW2kgLSAxXTtcblx0XHRkZWZlcnJlZFtpXSA9IFtjaHVua0lkcywgZm4sIHByaW9yaXR5XTtcblx0XHRyZXR1cm47XG5cdH1cblx0dmFyIG5vdEZ1bGZpbGxlZCA9IEluZmluaXR5O1xuXHRmb3IgKHZhciBpID0gMDsgaSA8IGRlZmVycmVkLmxlbmd0aDsgaSsrKSB7XG5cdFx0dmFyIFtjaHVua0lkcywgZm4sIHByaW9yaXR5XSA9IGRlZmVycmVkW2ldO1xuXHRcdHZhciBmdWxmaWxsZWQgPSB0cnVlO1xuXHRcdGZvciAodmFyIGogPSAwOyBqIDwgY2h1bmtJZHMubGVuZ3RoOyBqKyspIHtcblx0XHRcdGlmICgocHJpb3JpdHkgJiAxID09PSAwIHx8IG5vdEZ1bGZpbGxlZCA+PSBwcmlvcml0eSkgJiYgT2JqZWN0LmtleXMoX193ZWJwYWNrX3JlcXVpcmVfXy5PKS5ldmVyeSgoa2V5KSA9PiAoX193ZWJwYWNrX3JlcXVpcmVfXy5PW2tleV0oY2h1bmtJZHNbal0pKSkpIHtcblx0XHRcdFx0Y2h1bmtJZHMuc3BsaWNlKGotLSwgMSk7XG5cdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRmdWxmaWxsZWQgPSBmYWxzZTtcblx0XHRcdFx0aWYocHJpb3JpdHkgPCBub3RGdWxmaWxsZWQpIG5vdEZ1bGZpbGxlZCA9IHByaW9yaXR5O1xuXHRcdFx0fVxuXHRcdH1cblx0XHRpZihmdWxmaWxsZWQpIHtcblx0XHRcdGRlZmVycmVkLnNwbGljZShpLS0sIDEpXG5cdFx0XHR2YXIgciA9IGZuKCk7XG5cdFx0XHRpZiAociAhPT0gdW5kZWZpbmVkKSByZXN1bHQgPSByO1xuXHRcdH1cblx0fVxuXHRyZXR1cm4gcmVzdWx0O1xufTsiLCIvLyBnZXREZWZhdWx0RXhwb3J0IGZ1bmN0aW9uIGZvciBjb21wYXRpYmlsaXR5IHdpdGggbm9uLWhhcm1vbnkgbW9kdWxlc1xuX193ZWJwYWNrX3JlcXVpcmVfXy5uID0gKG1vZHVsZSkgPT4ge1xuXHR2YXIgZ2V0dGVyID0gbW9kdWxlICYmIG1vZHVsZS5fX2VzTW9kdWxlID9cblx0XHQoKSA9PiAobW9kdWxlWydkZWZhdWx0J10pIDpcblx0XHQoKSA9PiAobW9kdWxlKTtcblx0X193ZWJwYWNrX3JlcXVpcmVfXy5kKGdldHRlciwgeyBhOiBnZXR0ZXIgfSk7XG5cdHJldHVybiBnZXR0ZXI7XG59OyIsIi8vIGRlZmluZSBnZXR0ZXIgZnVuY3Rpb25zIGZvciBoYXJtb255IGV4cG9ydHNcbl9fd2VicGFja19yZXF1aXJlX18uZCA9IChleHBvcnRzLCBkZWZpbml0aW9uKSA9PiB7XG5cdGZvcih2YXIga2V5IGluIGRlZmluaXRpb24pIHtcblx0XHRpZihfX3dlYnBhY2tfcmVxdWlyZV9fLm8oZGVmaW5pdGlvbiwga2V5KSAmJiAhX193ZWJwYWNrX3JlcXVpcmVfXy5vKGV4cG9ydHMsIGtleSkpIHtcblx0XHRcdE9iamVjdC5kZWZpbmVQcm9wZXJ0eShleHBvcnRzLCBrZXksIHsgZW51bWVyYWJsZTogdHJ1ZSwgZ2V0OiBkZWZpbml0aW9uW2tleV0gfSk7XG5cdFx0fVxuXHR9XG59OyIsIl9fd2VicGFja19yZXF1aXJlX18ubyA9IChvYmosIHByb3ApID0+IChPYmplY3QucHJvdG90eXBlLmhhc093blByb3BlcnR5LmNhbGwob2JqLCBwcm9wKSkiLCIvLyBkZWZpbmUgX19lc01vZHVsZSBvbiBleHBvcnRzXG5fX3dlYnBhY2tfcmVxdWlyZV9fLnIgPSAoZXhwb3J0cykgPT4ge1xuXHRpZih0eXBlb2YgU3ltYm9sICE9PSAndW5kZWZpbmVkJyAmJiBTeW1ib2wudG9TdHJpbmdUYWcpIHtcblx0XHRPYmplY3QuZGVmaW5lUHJvcGVydHkoZXhwb3J0cywgU3ltYm9sLnRvU3RyaW5nVGFnLCB7IHZhbHVlOiAnTW9kdWxlJyB9KTtcblx0fVxuXHRPYmplY3QuZGVmaW5lUHJvcGVydHkoZXhwb3J0cywgJ19fZXNNb2R1bGUnLCB7IHZhbHVlOiB0cnVlIH0pO1xufTsiLCIvLyBubyBiYXNlVVJJXG5cbi8vIG9iamVjdCB0byBzdG9yZSBsb2FkZWQgYW5kIGxvYWRpbmcgY2h1bmtzXG4vLyB1bmRlZmluZWQgPSBjaHVuayBub3QgbG9hZGVkLCBudWxsID0gY2h1bmsgcHJlbG9hZGVkL3ByZWZldGNoZWRcbi8vIFtyZXNvbHZlLCByZWplY3QsIFByb21pc2VdID0gY2h1bmsgbG9hZGluZywgMCA9IGNodW5rIGxvYWRlZFxudmFyIGluc3RhbGxlZENodW5rcyA9IHtcblx0XCJkYXNoYm9hcmRcIjogMFxufTtcblxuLy8gbm8gY2h1bmsgb24gZGVtYW5kIGxvYWRpbmdcblxuLy8gbm8gcHJlZmV0Y2hpbmdcblxuLy8gbm8gcHJlbG9hZGVkXG5cbi8vIG5vIEhNUlxuXG4vLyBubyBITVIgbWFuaWZlc3RcblxuX193ZWJwYWNrX3JlcXVpcmVfXy5PLmogPSAoY2h1bmtJZCkgPT4gKGluc3RhbGxlZENodW5rc1tjaHVua0lkXSA9PT0gMCk7XG5cbi8vIGluc3RhbGwgYSBKU09OUCBjYWxsYmFjayBmb3IgY2h1bmsgbG9hZGluZ1xudmFyIHdlYnBhY2tKc29ucENhbGxiYWNrID0gKHBhcmVudENodW5rTG9hZGluZ0Z1bmN0aW9uLCBkYXRhKSA9PiB7XG5cdHZhciBbY2h1bmtJZHMsIG1vcmVNb2R1bGVzLCBydW50aW1lXSA9IGRhdGE7XG5cdC8vIGFkZCBcIm1vcmVNb2R1bGVzXCIgdG8gdGhlIG1vZHVsZXMgb2JqZWN0LFxuXHQvLyB0aGVuIGZsYWcgYWxsIFwiY2h1bmtJZHNcIiBhcyBsb2FkZWQgYW5kIGZpcmUgY2FsbGJhY2tcblx0dmFyIG1vZHVsZUlkLCBjaHVua0lkLCBpID0gMDtcblx0aWYoY2h1bmtJZHMuc29tZSgoaWQpID0+IChpbnN0YWxsZWRDaHVua3NbaWRdICE9PSAwKSkpIHtcblx0XHRmb3IobW9kdWxlSWQgaW4gbW9yZU1vZHVsZXMpIHtcblx0XHRcdGlmKF9fd2VicGFja19yZXF1aXJlX18ubyhtb3JlTW9kdWxlcywgbW9kdWxlSWQpKSB7XG5cdFx0XHRcdF9fd2VicGFja19yZXF1aXJlX18ubVttb2R1bGVJZF0gPSBtb3JlTW9kdWxlc1ttb2R1bGVJZF07XG5cdFx0XHR9XG5cdFx0fVxuXHRcdGlmKHJ1bnRpbWUpIHZhciByZXN1bHQgPSBydW50aW1lKF9fd2VicGFja19yZXF1aXJlX18pO1xuXHR9XG5cdGlmKHBhcmVudENodW5rTG9hZGluZ0Z1bmN0aW9uKSBwYXJlbnRDaHVua0xvYWRpbmdGdW5jdGlvbihkYXRhKTtcblx0Zm9yKDtpIDwgY2h1bmtJZHMubGVuZ3RoOyBpKyspIHtcblx0XHRjaHVua0lkID0gY2h1bmtJZHNbaV07XG5cdFx0aWYoX193ZWJwYWNrX3JlcXVpcmVfXy5vKGluc3RhbGxlZENodW5rcywgY2h1bmtJZCkgJiYgaW5zdGFsbGVkQ2h1bmtzW2NodW5rSWRdKSB7XG5cdFx0XHRpbnN0YWxsZWRDaHVua3NbY2h1bmtJZF1bMF0oKTtcblx0XHR9XG5cdFx0aW5zdGFsbGVkQ2h1bmtzW2NodW5rSWRdID0gMDtcblx0fVxuXHRyZXR1cm4gX193ZWJwYWNrX3JlcXVpcmVfXy5PKHJlc3VsdCk7XG59XG5cbnZhciBjaHVua0xvYWRpbmdHbG9iYWwgPSBnbG9iYWxUaGlzW1wid2VicGFja0NodW5rXCJdID0gZ2xvYmFsVGhpc1tcIndlYnBhY2tDaHVua1wiXSB8fCBbXTtcbmNodW5rTG9hZGluZ0dsb2JhbC5mb3JFYWNoKHdlYnBhY2tKc29ucENhbGxiYWNrLmJpbmQobnVsbCwgMCkpO1xuY2h1bmtMb2FkaW5nR2xvYmFsLnB1c2ggPSB3ZWJwYWNrSnNvbnBDYWxsYmFjay5iaW5kKG51bGwsIGNodW5rTG9hZGluZ0dsb2JhbC5wdXNoLmJpbmQoY2h1bmtMb2FkaW5nR2xvYmFsKSk7IiwiIiwiLy8gc3RhcnR1cFxuLy8gTG9hZCBlbnRyeSBtb2R1bGUgYW5kIHJldHVybiBleHBvcnRzXG4vLyBUaGlzIGVudHJ5IG1vZHVsZSBkZXBlbmRzIG9uIG90aGVyIGxvYWRlZCBjaHVua3MgYW5kIGV4ZWN1dGlvbiBuZWVkIHRvIGJlIGRlbGF5ZWRcbnZhciBfX3dlYnBhY2tfZXhwb3J0c19fID0gX193ZWJwYWNrX3JlcXVpcmVfXy5PKHVuZGVmaW5lZCwgW1widmVuZG9ycy1ub2RlX21vZHVsZXNfanF1ZXJ5X2Rpc3RfanF1ZXJ5X2pzXCIsXCJ2ZW5kb3JzLW5vZGVfbW9kdWxlc19mb3VuZGF0aW9uLWRhdGVwaWNrZXJfanNfZm91bmRhdGlvbi1kYXRlcGlja2VyX21pbl9qc1wiXSwgKCkgPT4gKF9fd2VicGFja19yZXF1aXJlX18oXCIuL2Fzc2V0cy9qcy9kYXNoYm9hcmQuanNcIikpKVxuX193ZWJwYWNrX2V4cG9ydHNfXyA9IF9fd2VicGFja19yZXF1aXJlX18uTyhfX3dlYnBhY2tfZXhwb3J0c19fKTtcbiIsIiJdLCJuYW1lcyI6WyIkIiwiZmRhdGVwaWNrZXIiLCJEaXNwbGF5IiwiY2hlY2tCb3giLCJkb2N1bWVudCIsImdldEVsZW1lbnRCeUlkIiwiY2hlY2tlZCIsImlubmVyVGV4dCIsIndpbmRvdyIsIm9ubG9hZCIsImFkZEV2ZW50TGlzdGVuZXIiLCJjbGVhciIsInNlYXJjaFBsYW50cyIsImNvbnNvbGUiLCJsb2ciLCJjaGFuZ2VEaXNwbGF5IiwiY29sbCIsInZhbHVlIiwiaSIsImxlbiIsImxlbmd0aCIsInN0eWxlIiwiaW5wdXQiLCJmaWx0ZXIiLCJ0b0xvd2VyQ2FzZSIsIm5vZGVzIiwiZ2V0RWxlbWVudHNCeUNsYXNzTmFtZSIsImluY2x1ZGVzIiwiZGlzcGxheSIsImNoYW5nZSIsInZhbCIsInN1Ym1pdCIsImxhbmd1YWdlIiwid2Vla1N0YXJ0Il0sInNvdXJjZVJvb3QiOiIifQ==