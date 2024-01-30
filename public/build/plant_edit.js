/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./assets/js/plant_edit.js":
/*!*********************************!*\
  !*** ./assets/js/plant_edit.js ***!
  \*********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);


// Sensors
let $wrapper_sensors = jquery__WEBPACK_IMPORTED_MODULE_0___default()('.js-sensors-wrapper');
$wrapper_sensors.on('click', '.js-remove-sensor', function (e) {
  e.preventDefault();
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).closest('.js-sensor-item').remove();
});
$wrapper_sensors.on('click', '.js-add-sensor', function (e) {
  e.preventDefault();
  let prototype = $wrapper_sensors.data('prototype');
  let index = $wrapper_sensors.data('index');
  let newForm = prototype.replace(/__name__/g, index);
  $wrapper_sensors.data('index', index + 1);
  jquery__WEBPACK_IMPORTED_MODULE_0___default()('#js-sensors>tbody').append(newForm);
  Foundation.reInit('accordion');
});

// Module
let $wrapper_module = jquery__WEBPACK_IMPORTED_MODULE_0___default()('.js-module-wrapper');
$wrapper_module.on('click', '.js-remove-module', function (e) {
  e.preventDefault();
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).closest('.js-module-item').remove();
});
$wrapper_module.on('click', '.js-add-module', function (e) {
  e.preventDefault();
  let prototype = $wrapper_module.data('prototype');
  let index = $wrapper_module.data('index');
  let newForm = prototype.replace(/__name__/g, index);
  $wrapper_module.data('index', index + 1);
  jquery__WEBPACK_IMPORTED_MODULE_0___default()('#modules>ul').append(newForm);
  Foundation.reInit('accordion');
});
/*
* MS  08/2023
* jquery delete sunshading data from fields
*/
// SunShading Wrapper
let $wrapper_sunshading = jquery__WEBPACK_IMPORTED_MODULE_0___default()('.js-sunshading-wrapper');
$wrapper_sunshading.on('click', '.js-remove-sunshading', function (e) {
  e.preventDefault();
  Swal.fire({
    title: "Are you sure?",
    text: "You want to delete a sunshading Model!",
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#126195",
    timer: 80000,
    confirmButtonText: "Yes, do it!",
    cancelButtonText: "No, cancel it!",
    showCloseButton: true,
    allowOutsideClick: false,
    allowEscapeKey: false,
    focusConfirm: true
  }).then(result => {
    if (result.isConfirmed) {
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).closest('.js-sunshading-item').remove();
    }
  });
});
// SunShading Add
jquery__WEBPACK_IMPORTED_MODULE_0___default()('.js-add-sunshading').on('click', function (e) {
  e.preventDefault();
  let prototype = $wrapper_sunshading.data('prototype');
  let index = $wrapper_sunshading.data('index');
  let newForm = prototype.replace(/__name__/g, index);
  $wrapper_sunshading.data('index', index + 1);
  jquery__WEBPACK_IMPORTED_MODULE_0___default()('#sunshading>ul').append(newForm);
  Foundation.reInit('accordion');
});
/*
* MS  08/2023
* jquery copy sunshading data into new input fields
*/
// SunShading Copy
jquery__WEBPACK_IMPORTED_MODULE_0___default()('.js-copy-sunshading').click(function () {
  // copy data from wrapper_sunshading
  let prototype = $wrapper_sunshading.data('prototype');
  let indexrow = $wrapper_sunshading.data('index') - 1;
  // predefine the id with [indexrow] from to copy value
  var cpfield0 = '#anlage_form_anlageSunShading_' + indexrow + '_description';
  var cpfield1 = '#anlage_form_anlageSunShading_' + indexrow + '_mod_tilt';
  var cpfield2 = '#anlage_form_anlageSunShading_' + indexrow + '_mod_height';
  var cpfield3 = '#anlage_form_anlageSunShading_' + indexrow + '_mod_width';
  var cpfield4 = '#anlage_form_anlageSunShading_' + indexrow + '_mod_table_height';
  var cpfield5 = '#anlage_form_anlageSunShading_' + indexrow + '_mod_table_distance';
  var cpfield6 = '#anlage_form_anlageSunShading_' + indexrow + '_distance_a';
  var cpfield7 = '#anlage_form_anlageSunShading_' + indexrow + '_distance_b';
  var cpfield8 = '#anlage_form_anlageSunShading_' + indexrow + '_ground_slope';
  var cpfield9 = '#anlage_form_anlageSunShading_' + indexrow + '_modulesDB';
  var cpfield10 = '#anlage_form_anlageSunShading_' + indexrow + '_has_row_shading';
  var cpfield11 = '#anlage_form_anlageSunShading_' + indexrow + '_mod_alignment';
  var cpfield12 = '#anlage_form_anlageSunShading_' + indexrow + '_mod_long_page';
  var cpfield13 = '#anlage_form_anlageSunShading_' + indexrow + '_mod_short_page';
  var cpfield14 = '#anlage_form_anlageSunShading_' + indexrow + '_mod_row_tables';
  // build the new wrapper
  let index = $wrapper_sunshading.data('index');
  let newForm = prototype.replace(/__name__/g, index);
  $wrapper_sunshading.data('index', index + 1);
  jquery__WEBPACK_IMPORTED_MODULE_0___default()('#sunshading>ul').append(newForm);
  indexrow = indexrow + 1;
  // predefine the insert id [indexrow] of value
  var nwfield0 = '#anlage_form_anlageSunShading_' + indexrow + '_description';
  var nwfield1 = '#anlage_form_anlageSunShading_' + indexrow + '_mod_tilt';
  var nwfield2 = '#anlage_form_anlageSunShading_' + indexrow + '_mod_height';
  var nwfield3 = '#anlage_form_anlageSunShading_' + indexrow + '_mod_width';
  var nwfield4 = '#anlage_form_anlageSunShading_' + indexrow + '_mod_table_height';
  var nwfield5 = '#anlage_form_anlageSunShading_' + indexrow + '_mod_table_distance';
  var nwfield6 = '#anlage_form_anlageSunShading_' + indexrow + '_distance_a';
  var nwfield7 = '#anlage_form_anlageSunShading_' + indexrow + '_distance_b';
  var nwfield8 = '#anlage_form_anlageSunShading_' + indexrow + '_ground_slope';
  var nwfield9 = '#anlage_form_anlageSunShading_' + indexrow + '_modulesDB';
  var nwfield10 = '#anlage_form_anlageSunShading_' + indexrow + '_has_row_shading';
  var nwfield11 = '#anlage_form_anlageSunShading_' + indexrow + '_mod_alignment';
  var nwfield12 = '#anlage_form_anlageSunShading_' + indexrow + '_mod_long_page';
  var nwfield13 = '#anlage_form_anlageSunShading_' + indexrow + '_mod_short_page';
  var nwfield14 = '#anlage_form_anlageSunShading_' + indexrow + '_mod_row_tables';
  // begin copy
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(nwfield0).val(jquery__WEBPACK_IMPORTED_MODULE_0___default()(cpfield0).val());
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(nwfield1).val(jquery__WEBPACK_IMPORTED_MODULE_0___default()(cpfield1).val());
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(nwfield2).val(jquery__WEBPACK_IMPORTED_MODULE_0___default()(cpfield2).val());
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(nwfield3).val(jquery__WEBPACK_IMPORTED_MODULE_0___default()(cpfield3).val());
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(nwfield4).val(jquery__WEBPACK_IMPORTED_MODULE_0___default()(cpfield4).val());
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(nwfield5).val(jquery__WEBPACK_IMPORTED_MODULE_0___default()(cpfield5).val());
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(nwfield6).val(jquery__WEBPACK_IMPORTED_MODULE_0___default()(cpfield6).val());
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(nwfield7).val(jquery__WEBPACK_IMPORTED_MODULE_0___default()(cpfield7).val());
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(nwfield8).val(jquery__WEBPACK_IMPORTED_MODULE_0___default()(cpfield8).val());
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(nwfield9).val(jquery__WEBPACK_IMPORTED_MODULE_0___default()(cpfield9).val());
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(nwfield10).val(jquery__WEBPACK_IMPORTED_MODULE_0___default()(cpfield10).val());
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(nwfield11).val(jquery__WEBPACK_IMPORTED_MODULE_0___default()(cpfield11).val());
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(nwfield12).val(jquery__WEBPACK_IMPORTED_MODULE_0___default()(cpfield12).val());
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(nwfield13).val(jquery__WEBPACK_IMPORTED_MODULE_0___default()(cpfield13).val());
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(nwfield14).val(jquery__WEBPACK_IMPORTED_MODULE_0___default()(cpfield14).val());
  // ende copy and reinitzial accordion
  jquery__WEBPACK_IMPORTED_MODULE_0___default()('#accordion-title').text('NEW Sun Shading Model from a COPY:');
  Foundation.reInit('accordion');
});

// the Time Config wrapper
let $wrapper_timeconfig = jquery__WEBPACK_IMPORTED_MODULE_0___default()('.js-timeConfig-wrapper');
$wrapper_timeconfig.on('click', '.js-remove-timeConfig-module', function (e) {
  e.preventDefault();
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).closest('.js-timeConfig-item').remove();
});
$wrapper_timeconfig.on('click', '.js-add-timeConfig', function (e) {
  e.preventDefault();
  let prototype = $wrapper_timeconfig.data('prototype');
  let index = $wrapper_timeconfig.data('index');
  let newForm = prototype.replace(/__name__/g, index);
  $wrapper_timeconfig.data('index', index + 1);
  jquery__WEBPACK_IMPORTED_MODULE_0___default()('#timeConfig>tbody').append(newForm);
});

// Event Mail
let $wrapper_eventmail = jquery__WEBPACK_IMPORTED_MODULE_0___default()('.js-eventmail-wrapper');
$wrapper_eventmail.on('click', '.js-remove-eventmail', function (e) {
  e.preventDefault();
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).closest('.js-eventmail-item').remove();
});
$wrapper_eventmail.on('click', '.js-add-eventmail', function (e) {
  e.preventDefault();
  let prototype = $wrapper_eventmail.data('prototype');
  let index = $wrapper_eventmail.data('index');
  let newForm = prototype.replace(/__name__/g, index);
  $wrapper_eventmail.data('index', index + 1);
  jquery__WEBPACK_IMPORTED_MODULE_0___default()('#even-mail>tbody').append(newForm);
});

// legend
let $wrapper_legend = jquery__WEBPACK_IMPORTED_MODULE_0___default()('.js-legend-_monthly-wrapper');
$wrapper_legend.on('click', '.js-remove-legend-_monthly', function (e) {
  e.preventDefault();
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).closest('.js-legend-_monthly-item').remove();
});
$wrapper_legend.on('click', '.js-add-legend-_monthly', function (e) {
  e.preventDefault();
  let prototype = $wrapper_legend.data('prototype');
  let index = $wrapper_legend.data('index');
  let newForm = prototype.replace(/__name__/g, index);
  $wrapper_legend.data('index', index + 1);
  jquery__WEBPACK_IMPORTED_MODULE_0___default()('#legend-_monthly>tbody').append(newForm);
});

// legend EPC
let $wrapper_epc = jquery__WEBPACK_IMPORTED_MODULE_0___default()('.js-legend-epc-wrapper');
$wrapper_epc.on('click', '.js-remove-legend-epc', function (e) {
  e.preventDefault();
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).closest('.js-legend-epc-item').remove();
});
$wrapper_epc.on('click', '.js-add-legend-epc', function (e) {
  e.preventDefault();
  let prototype = $wrapper_epc.data('prototype');
  let index = $wrapper_epc.data('index');
  let newForm = prototype.replace(/__name__/g, index);
  $wrapper_epc.data('index', index + 1);
  jquery__WEBPACK_IMPORTED_MODULE_0___default()('#legend-epc>tbody').append(newForm);
});

// pvsyst Design Werte
let $wrapper_pvsyst = jquery__WEBPACK_IMPORTED_MODULE_0___default()('.js-pvsyst-wrapper');
$wrapper_pvsyst.on('click', '.js-remove-pvsyst', function (e) {
  e.preventDefault();
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).closest('.js-pvsyst-item').remove();
});
$wrapper_pvsyst.on('click', '.js-add-pvsyst', function (e) {
  e.preventDefault();
  let prototype = $wrapper_pvsyst.data('prototype');
  let index = $wrapper_pvsyst.data('index');
  let newForm = prototype.replace(/__name__/g, index);
  $wrapper_pvsyst.data('index', index + 1);
  jquery__WEBPACK_IMPORTED_MODULE_0___default()('#pvsyst-values>tbody').append(newForm);
});

// _monthly-yield
let $wrapper_yield = jquery__WEBPACK_IMPORTED_MODULE_0___default()('.js-_monthly-yield-wrapper');
$wrapper_yield.on('click', '.js-remove-_monthly-yield', function (e) {
  e.preventDefault();
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).closest('.js-_monthly-yield-item').remove();
});
$wrapper_yield.on('click', '.js-add-_monthly-yield', function (e) {
  e.preventDefault();
  let prototype = $wrapper_yield.data('prototype');
  let index = $wrapper_yield.data('index');
  let newForm = prototype.replace(/__name__/g, index);
  $wrapper_yield.data('index', index + 1);
  jquery__WEBPACK_IMPORTED_MODULE_0___default()('#_monthly-yield-values>tbody').append(newForm);
});

// Economics
let $wrapper_economic = jquery__WEBPACK_IMPORTED_MODULE_0___default()('.js-economicVarValues-wrapper');
$wrapper_economic.on('click', '.js-economic-var-value-add', function (e) {
  e.preventDefault();
  let prototype = $wrapper_economic.data('prototype');
  let index = $wrapper_economic.data('index');
  let newForm = prototype.replace(/__name__/g, index);
  $wrapper_economic.data('index', index + 1);
  jquery__WEBPACK_IMPORTED_MODULE_0___default()('#economicsvalues-values>tbody').append(newForm);
});

// Gruppen
let $wrapper_group = jquery__WEBPACK_IMPORTED_MODULE_0___default()('.js-group-wrapper');
$wrapper_group.on('click', '.js-remove-group', function (e) {
  e.preventDefault();
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).closest('.js-group-item').remove();
});
$wrapper_group.on('click', '.js-add-group', function (e) {
  e.preventDefault();
  let prototype = $wrapper_group.data('prototype');
  let index = $wrapper_group.data('index');
  let newForm = prototype.replace(/__name__/g, index);
  $wrapper_group.data('index', index + 1);
  jquery__WEBPACK_IMPORTED_MODULE_0___default()('#group>ul').append(newForm);
  Foundation.reInit('accordion');
});

// Gruppen - Module
let $wrapper_use_module = jquery__WEBPACK_IMPORTED_MODULE_0___default()('.js-use-module-wrapper');
$wrapper_use_module.on('click', '.js-remove-use-module', function (e) {
  e.preventDefault();
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).closest('.js-use-module-item').remove();
});
$wrapper_use_module.on('click', '.js-add-use-module', function (e) {
  e.preventDefault();
  let prototype = $wrapper_use_module.data('prototype');
  let index = $wrapper_use_module.data('index');
  let groupId = e.currentTarget.dataset.groupid;
  let newForm = prototype.replace(/__name__/g, index).replace(/_groups_0/g, '_groups_' + (groupId - 1)).replace(/\[groups\]\[0\]/g, '\[groups\]\[' + (groupId - 1) + '\]');
  $wrapper_use_module.data('index', index + 1);
  jquery__WEBPACK_IMPORTED_MODULE_0___default()("#use-modules-" + groupId + ">tbody").append(newForm);
});

// Gruppen - Monate
let $wrapper_month = jquery__WEBPACK_IMPORTED_MODULE_0___default()('.js-month-wrapper');
$wrapper_month.on('click', '.js-remove-month', function (e) {
  e.preventDefault();
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).closest('.js-month-item').remove();
});
$wrapper_month.on('click', '.js-add-month', function (e) {
  e.preventDefault();
  let prototype = $wrapper_month.data('prototype');
  let index = $wrapper_month.data('index');
  let groupId = e.currentTarget.dataset.groupid;
  let newForm = prototype.replace(/__name__/g, index).replace(/_groups_0/g, '_groups_' + (groupId - 1)).replace(/\[groups\]\[0\]/g, '\[groups\]\[' + (groupId - 1) + '\]');
  $wrapper_month.data('index', index + 1);
  jquery__WEBPACK_IMPORTED_MODULE_0___default()('#months-' + groupId + '>tbody').append(newForm);
});

// Anlagen - Monate
let $wrapper_plant_month = jquery__WEBPACK_IMPORTED_MODULE_0___default()('.js-plant-month-wrapper');
$wrapper_plant_month.on('click', '.js-remove-plant-month', function (e) {
  e.preventDefault();
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).closest('.js-plant-month-item').remove();
});
$wrapper_plant_month.on('click', '.js-add-plant-month', function (e) {
  e.preventDefault();
  let prototype = $wrapper_plant_month.data('prototype');
  let index = $wrapper_plant_month.data('index');
  let newForm = prototype.replace(/__name__/g, index);
  $wrapper_plant_month.data('index', index + 1);
  jquery__WEBPACK_IMPORTED_MODULE_0___default()('#plant-months>tbody').append(newForm);
});

// AC Gruppe
let $wrapper_acgroup = jquery__WEBPACK_IMPORTED_MODULE_0___default()('.js-acgroup-wrapper');
$wrapper_acgroup.on('click', '.js-remove-acgroup', function (e) {
  e.preventDefault();
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).closest('.js-acgroup-item').remove();
});
$wrapper_acgroup.on('click', '.js-add-acgroup', function (e) {
  e.preventDefault();
  let prototype = $wrapper_acgroup.data('prototype');
  let index = $wrapper_acgroup.data('index');
  let newForm = prototype.replace(/__name__/g, index);
  $wrapper_acgroup.data('index', index + 1);
  jquery__WEBPACK_IMPORTED_MODULE_0___default()('#js-acgroup>tbody').append(newForm);
  Foundation.reInit('accordion');
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
/******/ 			"plant_edit": 0
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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["vendors-node_modules_jquery_dist_jquery_js"], () => (__webpack_require__("./assets/js/plant_edit.js")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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InNvdXJjZXMiOlsid2VicGFjazovLy8uL2Fzc2V0cy9qcy9wbGFudF9lZGl0LmpzIiwid2VicGFjazovLy93ZWJwYWNrL2Jvb3RzdHJhcCIsIndlYnBhY2s6Ly8vd2VicGFjay9ydW50aW1lL2NodW5rIGxvYWRlZCIsIndlYnBhY2s6Ly8vd2VicGFjay9ydW50aW1lL2NvbXBhdCBnZXQgZGVmYXVsdCBleHBvcnQiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svcnVudGltZS9kZWZpbmUgcHJvcGVydHkgZ2V0dGVycyIsIndlYnBhY2s6Ly8vd2VicGFjay9ydW50aW1lL2hhc093blByb3BlcnR5IHNob3J0aGFuZCIsIndlYnBhY2s6Ly8vd2VicGFjay9ydW50aW1lL21ha2UgbmFtZXNwYWNlIG9iamVjdCIsIndlYnBhY2s6Ly8vd2VicGFjay9ydW50aW1lL2pzb25wIGNodW5rIGxvYWRpbmciLCJ3ZWJwYWNrOi8vL3dlYnBhY2svYmVmb3JlLXN0YXJ0dXAiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svc3RhcnR1cCIsIndlYnBhY2s6Ly8vd2VicGFjay9hZnRlci1zdGFydHVwIl0sInNvdXJjZXNDb250ZW50IjpbImltcG9ydCAkIGZyb20gJ2pxdWVyeSc7XHJcblxyXG4vLyBTZW5zb3JzXHJcbmxldCAkd3JhcHBlcl9zZW5zb3JzID0gJCgnLmpzLXNlbnNvcnMtd3JhcHBlcicpO1xyXG4kd3JhcHBlcl9zZW5zb3JzLm9uKCdjbGljaycsICcuanMtcmVtb3ZlLXNlbnNvcicsIGZ1bmN0aW9uIChlKSB7XHJcbiAgICBlLnByZXZlbnREZWZhdWx0KCk7XHJcbiAgICAkKHRoaXMpLmNsb3Nlc3QoJy5qcy1zZW5zb3ItaXRlbScpXHJcbiAgICAgICAgLnJlbW92ZSgpO1xyXG59KTtcclxuJHdyYXBwZXJfc2Vuc29ycy5vbignY2xpY2snLCAnLmpzLWFkZC1zZW5zb3InLCBmdW5jdGlvbiAoZSkge1xyXG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xyXG4gICAgbGV0IHByb3RvdHlwZSA9ICR3cmFwcGVyX3NlbnNvcnMuZGF0YSgncHJvdG90eXBlJyk7XHJcbiAgICBsZXQgaW5kZXggPSAkd3JhcHBlcl9zZW5zb3JzLmRhdGEoJ2luZGV4Jyk7XHJcbiAgICBsZXQgbmV3Rm9ybSA9IHByb3RvdHlwZS5yZXBsYWNlKC9fX25hbWVfXy9nLCBpbmRleCk7XHJcbiAgICAkd3JhcHBlcl9zZW5zb3JzLmRhdGEoJ2luZGV4JywgaW5kZXggKyAxKTtcclxuICAgICQoJyNqcy1zZW5zb3JzPnRib2R5JykuYXBwZW5kKG5ld0Zvcm0pO1xyXG4gICAgRm91bmRhdGlvbi5yZUluaXQoJ2FjY29yZGlvbicpO1xyXG59KTtcclxuXHJcbi8vIE1vZHVsZVxyXG5sZXQgJHdyYXBwZXJfbW9kdWxlID0gJCgnLmpzLW1vZHVsZS13cmFwcGVyJyk7XHJcbiR3cmFwcGVyX21vZHVsZS5vbignY2xpY2snLCAnLmpzLXJlbW92ZS1tb2R1bGUnLCBmdW5jdGlvbiAoZSkge1xyXG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xyXG4gICAgJCh0aGlzKS5jbG9zZXN0KCcuanMtbW9kdWxlLWl0ZW0nKVxyXG4gICAgICAgIC5yZW1vdmUoKTtcclxufSk7XHJcbiR3cmFwcGVyX21vZHVsZS5vbignY2xpY2snLCAnLmpzLWFkZC1tb2R1bGUnLCBmdW5jdGlvbiAoZSkge1xyXG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xyXG4gICAgbGV0IHByb3RvdHlwZSA9ICR3cmFwcGVyX21vZHVsZS5kYXRhKCdwcm90b3R5cGUnKTtcclxuICAgIGxldCBpbmRleCA9ICR3cmFwcGVyX21vZHVsZS5kYXRhKCdpbmRleCcpO1xyXG4gICAgbGV0IG5ld0Zvcm0gPSBwcm90b3R5cGUucmVwbGFjZSgvX19uYW1lX18vZywgaW5kZXgpO1xyXG4gICAgJHdyYXBwZXJfbW9kdWxlLmRhdGEoJ2luZGV4JywgaW5kZXggKyAxKTtcclxuICAgICQoJyNtb2R1bGVzPnVsJykuYXBwZW5kKG5ld0Zvcm0pO1xyXG4gICAgRm91bmRhdGlvbi5yZUluaXQoJ2FjY29yZGlvbicpO1xyXG59KTtcclxuLypcclxuKiBNUyAgMDgvMjAyM1xyXG4qIGpxdWVyeSBkZWxldGUgc3Vuc2hhZGluZyBkYXRhIGZyb20gZmllbGRzXHJcbiovXHJcbi8vIFN1blNoYWRpbmcgV3JhcHBlclxyXG5sZXQgJHdyYXBwZXJfc3Vuc2hhZGluZyA9ICQoJy5qcy1zdW5zaGFkaW5nLXdyYXBwZXInKTtcclxuJHdyYXBwZXJfc3Vuc2hhZGluZy5vbignY2xpY2snLCAnLmpzLXJlbW92ZS1zdW5zaGFkaW5nJywgZnVuY3Rpb24gKGUpIHtcclxuICAgIGUucHJldmVudERlZmF1bHQoKTtcclxuICAgIFN3YWwuZmlyZSh7XHJcbiAgICAgICAgdGl0bGU6IFwiQXJlIHlvdSBzdXJlP1wiLFxyXG4gICAgICAgIHRleHQ6IFwiWW91IHdhbnQgdG8gZGVsZXRlIGEgc3Vuc2hhZGluZyBNb2RlbCFcIixcclxuICAgICAgICBpY29uOiBcInF1ZXN0aW9uXCIsXHJcbiAgICAgICAgc2hvd0NhbmNlbEJ1dHRvbjogdHJ1ZSxcclxuICAgICAgICBjb25maXJtQnV0dG9uQ29sb3I6IFwiIzEyNjE5NVwiLFxyXG4gICAgICAgIHRpbWVyOiA4MDAwMCxcclxuICAgICAgICBjb25maXJtQnV0dG9uVGV4dDogXCJZZXMsIGRvIGl0IVwiLFxyXG4gICAgICAgIGNhbmNlbEJ1dHRvblRleHQ6IFwiTm8sIGNhbmNlbCBpdCFcIixcclxuICAgICAgICBzaG93Q2xvc2VCdXR0b246IHRydWUsXHJcbiAgICAgICAgYWxsb3dPdXRzaWRlQ2xpY2s6IGZhbHNlLFxyXG4gICAgICAgIGFsbG93RXNjYXBlS2V5OiBmYWxzZSxcclxuICAgICAgICBmb2N1c0NvbmZpcm06IHRydWVcclxuICAgIH0pLnRoZW4oKHJlc3VsdCkgPT4ge1xyXG4gICAgICAgIGlmIChyZXN1bHQuaXNDb25maXJtZWQpIHtcclxuICAgICAgICAgICAkKHRoaXMpLmNsb3Nlc3QoJy5qcy1zdW5zaGFkaW5nLWl0ZW0nKS5yZW1vdmUoKTtcclxuICAgICAgICB9XHJcbiAgICB9KTtcclxuXHJcbn0pO1xyXG4vLyBTdW5TaGFkaW5nIEFkZFxyXG4kKCcuanMtYWRkLXN1bnNoYWRpbmcnKS5vbignY2xpY2snLCAgIGZ1bmN0aW9uKGUpIHtcclxuICAgIGUucHJldmVudERlZmF1bHQoKTtcclxuICAgIGxldCBwcm90b3R5cGUgPSAkd3JhcHBlcl9zdW5zaGFkaW5nLmRhdGEoJ3Byb3RvdHlwZScpO1xyXG4gICAgbGV0IGluZGV4ID0gJHdyYXBwZXJfc3Vuc2hhZGluZy5kYXRhKCdpbmRleCcpO1xyXG4gICAgbGV0IG5ld0Zvcm0gPSBwcm90b3R5cGUucmVwbGFjZSgvX19uYW1lX18vZywgaW5kZXgpO1xyXG4gICAgJHdyYXBwZXJfc3Vuc2hhZGluZy5kYXRhKCdpbmRleCcsIGluZGV4ICsgMSk7XHJcbiAgICAkKCcjc3Vuc2hhZGluZz51bCcpLmFwcGVuZChuZXdGb3JtKTtcclxuICAgIEZvdW5kYXRpb24ucmVJbml0KCdhY2NvcmRpb24nKTtcclxufSApO1xyXG4vKlxyXG4qIE1TICAwOC8yMDIzXHJcbioganF1ZXJ5IGNvcHkgc3Vuc2hhZGluZyBkYXRhIGludG8gbmV3IGlucHV0IGZpZWxkc1xyXG4qL1xyXG4vLyBTdW5TaGFkaW5nIENvcHlcclxuJCgnLmpzLWNvcHktc3Vuc2hhZGluZycpLmNsaWNrKGZ1bmN0aW9uKCkge1xyXG4gICAgLy8gY29weSBkYXRhIGZyb20gd3JhcHBlcl9zdW5zaGFkaW5nXHJcbiAgICBsZXQgcHJvdG90eXBlID0gJHdyYXBwZXJfc3Vuc2hhZGluZy5kYXRhKCdwcm90b3R5cGUnKTtcclxuICAgIGxldCBpbmRleHJvdyA9ICR3cmFwcGVyX3N1bnNoYWRpbmcuZGF0YSgnaW5kZXgnKSAtMTtcclxuICAgIC8vIHByZWRlZmluZSB0aGUgaWQgd2l0aCBbaW5kZXhyb3ddIGZyb20gdG8gY29weSB2YWx1ZVxyXG4gICAgdmFyIGNwZmllbGQwID0nI2FubGFnZV9mb3JtX2FubGFnZVN1blNoYWRpbmdfJytpbmRleHJvdysnX2Rlc2NyaXB0aW9uJztcclxuICAgIHZhciBjcGZpZWxkMSA9JyNhbmxhZ2VfZm9ybV9hbmxhZ2VTdW5TaGFkaW5nXycraW5kZXhyb3crJ19tb2RfdGlsdCc7XHJcbiAgICB2YXIgY3BmaWVsZDIgPScjYW5sYWdlX2Zvcm1fYW5sYWdlU3VuU2hhZGluZ18nK2luZGV4cm93KydfbW9kX2hlaWdodCc7XHJcbiAgICB2YXIgY3BmaWVsZDMgPScjYW5sYWdlX2Zvcm1fYW5sYWdlU3VuU2hhZGluZ18nK2luZGV4cm93KydfbW9kX3dpZHRoJztcclxuICAgIHZhciBjcGZpZWxkNCA9JyNhbmxhZ2VfZm9ybV9hbmxhZ2VTdW5TaGFkaW5nXycraW5kZXhyb3crJ19tb2RfdGFibGVfaGVpZ2h0JztcclxuICAgIHZhciBjcGZpZWxkNSA9JyNhbmxhZ2VfZm9ybV9hbmxhZ2VTdW5TaGFkaW5nXycraW5kZXhyb3crJ19tb2RfdGFibGVfZGlzdGFuY2UnO1xyXG4gICAgdmFyIGNwZmllbGQ2ID0nI2FubGFnZV9mb3JtX2FubGFnZVN1blNoYWRpbmdfJytpbmRleHJvdysnX2Rpc3RhbmNlX2EnO1xyXG4gICAgdmFyIGNwZmllbGQ3ID0nI2FubGFnZV9mb3JtX2FubGFnZVN1blNoYWRpbmdfJytpbmRleHJvdysnX2Rpc3RhbmNlX2InO1xyXG4gICAgdmFyIGNwZmllbGQ4ID0nI2FubGFnZV9mb3JtX2FubGFnZVN1blNoYWRpbmdfJytpbmRleHJvdysnX2dyb3VuZF9zbG9wZSc7XHJcbiAgICB2YXIgY3BmaWVsZDkgPScjYW5sYWdlX2Zvcm1fYW5sYWdlU3VuU2hhZGluZ18nK2luZGV4cm93KydfbW9kdWxlc0RCJztcclxuICAgIHZhciBjcGZpZWxkMTAgPScjYW5sYWdlX2Zvcm1fYW5sYWdlU3VuU2hhZGluZ18nK2luZGV4cm93KydfaGFzX3Jvd19zaGFkaW5nJztcclxuICAgIHZhciBjcGZpZWxkMTEgPScjYW5sYWdlX2Zvcm1fYW5sYWdlU3VuU2hhZGluZ18nK2luZGV4cm93KydfbW9kX2FsaWdubWVudCc7XHJcbiAgICB2YXIgY3BmaWVsZDEyID0nI2FubGFnZV9mb3JtX2FubGFnZVN1blNoYWRpbmdfJytpbmRleHJvdysnX21vZF9sb25nX3BhZ2UnO1xyXG4gICAgdmFyIGNwZmllbGQxMyA9JyNhbmxhZ2VfZm9ybV9hbmxhZ2VTdW5TaGFkaW5nXycraW5kZXhyb3crJ19tb2Rfc2hvcnRfcGFnZSc7XHJcbiAgICB2YXIgY3BmaWVsZDE0ID0nI2FubGFnZV9mb3JtX2FubGFnZVN1blNoYWRpbmdfJytpbmRleHJvdysnX21vZF9yb3dfdGFibGVzJztcclxuICAgIC8vIGJ1aWxkIHRoZSBuZXcgd3JhcHBlclxyXG4gICAgbGV0IGluZGV4ID0gJHdyYXBwZXJfc3Vuc2hhZGluZy5kYXRhKCdpbmRleCcpO1xyXG4gICAgbGV0IG5ld0Zvcm0gPSBwcm90b3R5cGUucmVwbGFjZSgvX19uYW1lX18vZywgaW5kZXgpO1xyXG4gICAgJHdyYXBwZXJfc3Vuc2hhZGluZy5kYXRhKCdpbmRleCcsIGluZGV4ICsgMSk7XHJcbiAgICAkKCcjc3Vuc2hhZGluZz51bCcpLmFwcGVuZChuZXdGb3JtKTtcclxuICAgIGluZGV4cm93ID0gaW5kZXhyb3cgKyAxO1xyXG4gICAgLy8gcHJlZGVmaW5lIHRoZSBpbnNlcnQgaWQgW2luZGV4cm93XSBvZiB2YWx1ZVxyXG4gICAgdmFyIG53ZmllbGQwID0nI2FubGFnZV9mb3JtX2FubGFnZVN1blNoYWRpbmdfJytpbmRleHJvdysnX2Rlc2NyaXB0aW9uJztcclxuICAgIHZhciBud2ZpZWxkMSA9JyNhbmxhZ2VfZm9ybV9hbmxhZ2VTdW5TaGFkaW5nXycraW5kZXhyb3crJ19tb2RfdGlsdCc7XHJcbiAgICB2YXIgbndmaWVsZDIgPScjYW5sYWdlX2Zvcm1fYW5sYWdlU3VuU2hhZGluZ18nK2luZGV4cm93KydfbW9kX2hlaWdodCc7XHJcbiAgICB2YXIgbndmaWVsZDMgPScjYW5sYWdlX2Zvcm1fYW5sYWdlU3VuU2hhZGluZ18nK2luZGV4cm93KydfbW9kX3dpZHRoJztcclxuICAgIHZhciBud2ZpZWxkNCA9JyNhbmxhZ2VfZm9ybV9hbmxhZ2VTdW5TaGFkaW5nXycraW5kZXhyb3crJ19tb2RfdGFibGVfaGVpZ2h0JztcclxuICAgIHZhciBud2ZpZWxkNSA9JyNhbmxhZ2VfZm9ybV9hbmxhZ2VTdW5TaGFkaW5nXycraW5kZXhyb3crJ19tb2RfdGFibGVfZGlzdGFuY2UnO1xyXG4gICAgdmFyIG53ZmllbGQ2ID0nI2FubGFnZV9mb3JtX2FubGFnZVN1blNoYWRpbmdfJytpbmRleHJvdysnX2Rpc3RhbmNlX2EnO1xyXG4gICAgdmFyIG53ZmllbGQ3ID0nI2FubGFnZV9mb3JtX2FubGFnZVN1blNoYWRpbmdfJytpbmRleHJvdysnX2Rpc3RhbmNlX2InO1xyXG4gICAgdmFyIG53ZmllbGQ4ID0nI2FubGFnZV9mb3JtX2FubGFnZVN1blNoYWRpbmdfJytpbmRleHJvdysnX2dyb3VuZF9zbG9wZSc7XHJcbiAgICB2YXIgbndmaWVsZDkgPScjYW5sYWdlX2Zvcm1fYW5sYWdlU3VuU2hhZGluZ18nK2luZGV4cm93KydfbW9kdWxlc0RCJztcclxuICAgIHZhciBud2ZpZWxkMTAgPScjYW5sYWdlX2Zvcm1fYW5sYWdlU3VuU2hhZGluZ18nK2luZGV4cm93KydfaGFzX3Jvd19zaGFkaW5nJztcclxuICAgIHZhciBud2ZpZWxkMTEgPScjYW5sYWdlX2Zvcm1fYW5sYWdlU3VuU2hhZGluZ18nK2luZGV4cm93KydfbW9kX2FsaWdubWVudCc7XHJcbiAgICB2YXIgbndmaWVsZDEyID0nI2FubGFnZV9mb3JtX2FubGFnZVN1blNoYWRpbmdfJytpbmRleHJvdysnX21vZF9sb25nX3BhZ2UnO1xyXG4gICAgdmFyIG53ZmllbGQxMyA9JyNhbmxhZ2VfZm9ybV9hbmxhZ2VTdW5TaGFkaW5nXycraW5kZXhyb3crJ19tb2Rfc2hvcnRfcGFnZSc7XHJcbiAgICB2YXIgbndmaWVsZDE0ID0nI2FubGFnZV9mb3JtX2FubGFnZVN1blNoYWRpbmdfJytpbmRleHJvdysnX21vZF9yb3dfdGFibGVzJztcclxuICAgIC8vIGJlZ2luIGNvcHlcclxuICAgICQobndmaWVsZDApLnZhbCgkKGNwZmllbGQwKS52YWwoKSk7XHJcbiAgICAkKG53ZmllbGQxKS52YWwoJChjcGZpZWxkMSkudmFsKCkpO1xyXG4gICAgJChud2ZpZWxkMikudmFsKCQoY3BmaWVsZDIpLnZhbCgpKTtcclxuICAgICQobndmaWVsZDMpLnZhbCgkKGNwZmllbGQzKS52YWwoKSk7XHJcbiAgICAkKG53ZmllbGQ0KS52YWwoJChjcGZpZWxkNCkudmFsKCkpO1xyXG4gICAgJChud2ZpZWxkNSkudmFsKCQoY3BmaWVsZDUpLnZhbCgpKTtcclxuICAgICQobndmaWVsZDYpLnZhbCgkKGNwZmllbGQ2KS52YWwoKSk7XHJcbiAgICAkKG53ZmllbGQ3KS52YWwoJChjcGZpZWxkNykudmFsKCkpO1xyXG4gICAgJChud2ZpZWxkOCkudmFsKCQoY3BmaWVsZDgpLnZhbCgpKTtcclxuICAgICQobndmaWVsZDkpLnZhbCgkKGNwZmllbGQ5KS52YWwoKSk7XHJcbiAgICAkKG53ZmllbGQxMCkudmFsKCQoY3BmaWVsZDEwKS52YWwoKSk7XHJcbiAgICAkKG53ZmllbGQxMSkudmFsKCQoY3BmaWVsZDExKS52YWwoKSk7XHJcbiAgICAkKG53ZmllbGQxMikudmFsKCQoY3BmaWVsZDEyKS52YWwoKSk7XHJcbiAgICAkKG53ZmllbGQxMykudmFsKCQoY3BmaWVsZDEzKS52YWwoKSk7XHJcbiAgICAkKG53ZmllbGQxNCkudmFsKCQoY3BmaWVsZDE0KS52YWwoKSk7XHJcbiAgICAvLyBlbmRlIGNvcHkgYW5kIHJlaW5pdHppYWwgYWNjb3JkaW9uXHJcbiAgICAkKCcjYWNjb3JkaW9uLXRpdGxlJykudGV4dCgnTkVXIFN1biBTaGFkaW5nIE1vZGVsIGZyb20gYSBDT1BZOicpO1xyXG4gICAgRm91bmRhdGlvbi5yZUluaXQoJ2FjY29yZGlvbicpO1xyXG59ICk7XHJcblxyXG4vLyB0aGUgVGltZSBDb25maWcgd3JhcHBlclxyXG5sZXQgJHdyYXBwZXJfdGltZWNvbmZpZyA9ICQoJy5qcy10aW1lQ29uZmlnLXdyYXBwZXInKTtcclxuJHdyYXBwZXJfdGltZWNvbmZpZy5vbignY2xpY2snLCAnLmpzLXJlbW92ZS10aW1lQ29uZmlnLW1vZHVsZScsIGZ1bmN0aW9uIChlKSB7XHJcbiAgICBlLnByZXZlbnREZWZhdWx0KCk7XHJcbiAgICAkKHRoaXMpLmNsb3Nlc3QoJy5qcy10aW1lQ29uZmlnLWl0ZW0nKVxyXG4gICAgICAgIC5yZW1vdmUoKTtcclxufSk7XHJcbiR3cmFwcGVyX3RpbWVjb25maWcub24oJ2NsaWNrJywgJy5qcy1hZGQtdGltZUNvbmZpZycsIGZ1bmN0aW9uIChlKSB7XHJcbiAgICBlLnByZXZlbnREZWZhdWx0KCk7XHJcbiAgICBsZXQgcHJvdG90eXBlID0gJHdyYXBwZXJfdGltZWNvbmZpZy5kYXRhKCdwcm90b3R5cGUnKTtcclxuICAgIGxldCBpbmRleCA9ICR3cmFwcGVyX3RpbWVjb25maWcuZGF0YSgnaW5kZXgnKTtcclxuICAgIGxldCBuZXdGb3JtID0gcHJvdG90eXBlLnJlcGxhY2UoL19fbmFtZV9fL2csIGluZGV4KTtcclxuICAgICR3cmFwcGVyX3RpbWVjb25maWcuZGF0YSgnaW5kZXgnLCBpbmRleCArIDEpO1xyXG4gICAgJCgnI3RpbWVDb25maWc+dGJvZHknKS5hcHBlbmQobmV3Rm9ybSk7XHJcbn0pO1xyXG5cclxuLy8gRXZlbnQgTWFpbFxyXG5sZXQgJHdyYXBwZXJfZXZlbnRtYWlsID0gJCgnLmpzLWV2ZW50bWFpbC13cmFwcGVyJyk7XHJcbiR3cmFwcGVyX2V2ZW50bWFpbC5vbignY2xpY2snLCAnLmpzLXJlbW92ZS1ldmVudG1haWwnLCBmdW5jdGlvbihlKSB7XHJcbiAgICBlLnByZXZlbnREZWZhdWx0KCk7XHJcbiAgICAkKHRoaXMpLmNsb3Nlc3QoJy5qcy1ldmVudG1haWwtaXRlbScpXHJcbiAgICAgICAgLnJlbW92ZSgpO1xyXG59KTtcclxuJHdyYXBwZXJfZXZlbnRtYWlsLm9uKCdjbGljaycsICcuanMtYWRkLWV2ZW50bWFpbCcsIGZ1bmN0aW9uKGUpIHtcclxuICAgIGUucHJldmVudERlZmF1bHQoKTtcclxuICAgIGxldCBwcm90b3R5cGUgPSAkd3JhcHBlcl9ldmVudG1haWwuZGF0YSgncHJvdG90eXBlJyk7XHJcbiAgICBsZXQgaW5kZXggPSAkd3JhcHBlcl9ldmVudG1haWwuZGF0YSgnaW5kZXgnKTtcclxuICAgIGxldCBuZXdGb3JtID0gcHJvdG90eXBlLnJlcGxhY2UoL19fbmFtZV9fL2csIGluZGV4KTtcclxuICAgICR3cmFwcGVyX2V2ZW50bWFpbC5kYXRhKCdpbmRleCcsIGluZGV4ICsgMSk7XHJcbiAgICAkKCcjZXZlbi1tYWlsPnRib2R5JykuYXBwZW5kKG5ld0Zvcm0pO1xyXG59KTtcclxuXHJcbi8vIGxlZ2VuZFxyXG5sZXQgJHdyYXBwZXJfbGVnZW5kID0gJCgnLmpzLWxlZ2VuZC1fbW9udGhseS13cmFwcGVyJyk7XHJcbiR3cmFwcGVyX2xlZ2VuZC5vbignY2xpY2snLCAnLmpzLXJlbW92ZS1sZWdlbmQtX21vbnRobHknLCBmdW5jdGlvbihlKSB7XHJcbiAgICBlLnByZXZlbnREZWZhdWx0KCk7XHJcbiAgICAkKHRoaXMpLmNsb3Nlc3QoJy5qcy1sZWdlbmQtX21vbnRobHktaXRlbScpXHJcbiAgICAgICAgLnJlbW92ZSgpO1xyXG59KTtcclxuJHdyYXBwZXJfbGVnZW5kLm9uKCdjbGljaycsICcuanMtYWRkLWxlZ2VuZC1fbW9udGhseScsIGZ1bmN0aW9uKGUpIHtcclxuICAgIGUucHJldmVudERlZmF1bHQoKTtcclxuICAgIGxldCBwcm90b3R5cGUgPSAkd3JhcHBlcl9sZWdlbmQuZGF0YSgncHJvdG90eXBlJyk7XHJcbiAgICBsZXQgaW5kZXggPSAkd3JhcHBlcl9sZWdlbmQuZGF0YSgnaW5kZXgnKTtcclxuICAgIGxldCBuZXdGb3JtID0gcHJvdG90eXBlLnJlcGxhY2UoL19fbmFtZV9fL2csIGluZGV4KTtcclxuICAgICR3cmFwcGVyX2xlZ2VuZC5kYXRhKCdpbmRleCcsIGluZGV4ICsgMSk7XHJcbiAgICAkKCcjbGVnZW5kLV9tb250aGx5PnRib2R5JykuYXBwZW5kKG5ld0Zvcm0pO1xyXG59KTtcclxuXHJcbi8vIGxlZ2VuZCBFUENcclxubGV0ICR3cmFwcGVyX2VwYyA9ICQoJy5qcy1sZWdlbmQtZXBjLXdyYXBwZXInKTtcclxuJHdyYXBwZXJfZXBjLm9uKCdjbGljaycsICcuanMtcmVtb3ZlLWxlZ2VuZC1lcGMnLCBmdW5jdGlvbihlKSB7XHJcbiAgICBlLnByZXZlbnREZWZhdWx0KCk7XHJcbiAgICAkKHRoaXMpLmNsb3Nlc3QoJy5qcy1sZWdlbmQtZXBjLWl0ZW0nKVxyXG4gICAgICAgIC5yZW1vdmUoKTtcclxufSk7XHJcbiR3cmFwcGVyX2VwYy5vbignY2xpY2snLCAnLmpzLWFkZC1sZWdlbmQtZXBjJywgZnVuY3Rpb24oZSkge1xyXG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xyXG4gICAgbGV0IHByb3RvdHlwZSA9ICR3cmFwcGVyX2VwYy5kYXRhKCdwcm90b3R5cGUnKTtcclxuICAgIGxldCBpbmRleCA9ICR3cmFwcGVyX2VwYy5kYXRhKCdpbmRleCcpO1xyXG4gICAgbGV0IG5ld0Zvcm0gPSBwcm90b3R5cGUucmVwbGFjZSgvX19uYW1lX18vZywgaW5kZXgpO1xyXG4gICAgJHdyYXBwZXJfZXBjLmRhdGEoJ2luZGV4JywgaW5kZXggKyAxKTtcclxuICAgICQoJyNsZWdlbmQtZXBjPnRib2R5JykuYXBwZW5kKG5ld0Zvcm0pO1xyXG59KTtcclxuXHJcbi8vIHB2c3lzdCBEZXNpZ24gV2VydGVcclxubGV0ICR3cmFwcGVyX3B2c3lzdCA9ICQoJy5qcy1wdnN5c3Qtd3JhcHBlcicpO1xyXG4kd3JhcHBlcl9wdnN5c3Qub24oJ2NsaWNrJywgJy5qcy1yZW1vdmUtcHZzeXN0JywgZnVuY3Rpb24oZSkge1xyXG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xyXG4gICAgJCh0aGlzKS5jbG9zZXN0KCcuanMtcHZzeXN0LWl0ZW0nKVxyXG4gICAgICAgIC5yZW1vdmUoKTtcclxufSk7XHJcbiR3cmFwcGVyX3B2c3lzdC5vbignY2xpY2snLCAnLmpzLWFkZC1wdnN5c3QnLCBmdW5jdGlvbihlKSB7XHJcbiAgICBlLnByZXZlbnREZWZhdWx0KCk7XHJcbiAgICBsZXQgcHJvdG90eXBlID0gJHdyYXBwZXJfcHZzeXN0LmRhdGEoJ3Byb3RvdHlwZScpO1xyXG4gICAgbGV0IGluZGV4ID0gJHdyYXBwZXJfcHZzeXN0LmRhdGEoJ2luZGV4Jyk7XHJcbiAgICBsZXQgbmV3Rm9ybSA9IHByb3RvdHlwZS5yZXBsYWNlKC9fX25hbWVfXy9nLCBpbmRleCk7XHJcbiAgICAkd3JhcHBlcl9wdnN5c3QuZGF0YSgnaW5kZXgnLCBpbmRleCArIDEpO1xyXG4gICAgJCgnI3B2c3lzdC12YWx1ZXM+dGJvZHknKS5hcHBlbmQobmV3Rm9ybSk7XHJcbn0pO1xyXG5cclxuLy8gX21vbnRobHkteWllbGRcclxubGV0ICR3cmFwcGVyX3lpZWxkID0gJCgnLmpzLV9tb250aGx5LXlpZWxkLXdyYXBwZXInKTtcclxuJHdyYXBwZXJfeWllbGQub24oJ2NsaWNrJywgJy5qcy1yZW1vdmUtX21vbnRobHkteWllbGQnLCBmdW5jdGlvbihlKSB7XHJcbiAgICBlLnByZXZlbnREZWZhdWx0KCk7XHJcbiAgICAkKHRoaXMpLmNsb3Nlc3QoJy5qcy1fbW9udGhseS15aWVsZC1pdGVtJylcclxuICAgICAgICAucmVtb3ZlKCk7XHJcbn0pO1xyXG4kd3JhcHBlcl95aWVsZC5vbignY2xpY2snLCAnLmpzLWFkZC1fbW9udGhseS15aWVsZCcsIGZ1bmN0aW9uKGUpIHtcclxuICAgIGUucHJldmVudERlZmF1bHQoKTtcclxuICAgIGxldCBwcm90b3R5cGUgPSAkd3JhcHBlcl95aWVsZC5kYXRhKCdwcm90b3R5cGUnKTtcclxuICAgIGxldCBpbmRleCA9ICR3cmFwcGVyX3lpZWxkLmRhdGEoJ2luZGV4Jyk7XHJcbiAgICBsZXQgbmV3Rm9ybSA9IHByb3RvdHlwZS5yZXBsYWNlKC9fX25hbWVfXy9nLCBpbmRleCk7XHJcbiAgICAkd3JhcHBlcl95aWVsZC5kYXRhKCdpbmRleCcsIGluZGV4ICsgMSk7XHJcbiAgICAkKCcjX21vbnRobHkteWllbGQtdmFsdWVzPnRib2R5JykuYXBwZW5kKG5ld0Zvcm0pO1xyXG59KTtcclxuXHJcbi8vIEVjb25vbWljc1xyXG5sZXQgJHdyYXBwZXJfZWNvbm9taWMgPSAkKCcuanMtZWNvbm9taWNWYXJWYWx1ZXMtd3JhcHBlcicpO1xyXG4kd3JhcHBlcl9lY29ub21pYy5vbignY2xpY2snLCAnLmpzLWVjb25vbWljLXZhci12YWx1ZS1hZGQnLGZ1bmN0aW9uKGUpe1xyXG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xyXG4gICAgbGV0IHByb3RvdHlwZSA9ICR3cmFwcGVyX2Vjb25vbWljLmRhdGEoJ3Byb3RvdHlwZScpO1xyXG4gICAgbGV0IGluZGV4ID0gJHdyYXBwZXJfZWNvbm9taWMuZGF0YSgnaW5kZXgnKTtcclxuICAgIGxldCBuZXdGb3JtID0gcHJvdG90eXBlLnJlcGxhY2UoL19fbmFtZV9fL2csIGluZGV4KTtcclxuICAgICR3cmFwcGVyX2Vjb25vbWljLmRhdGEoJ2luZGV4JywgaW5kZXgrMSk7XHJcbiAgICAkKCcjZWNvbm9taWNzdmFsdWVzLXZhbHVlcz50Ym9keScpLmFwcGVuZChuZXdGb3JtKTtcclxufSlcclxuXHJcbi8vIEdydXBwZW5cclxubGV0ICR3cmFwcGVyX2dyb3VwID0gJCgnLmpzLWdyb3VwLXdyYXBwZXInKTtcclxuJHdyYXBwZXJfZ3JvdXAub24oJ2NsaWNrJywgJy5qcy1yZW1vdmUtZ3JvdXAnLCBmdW5jdGlvbihlKSB7XHJcbiAgICBlLnByZXZlbnREZWZhdWx0KCk7XHJcbiAgICAkKHRoaXMpLmNsb3Nlc3QoJy5qcy1ncm91cC1pdGVtJylcclxuICAgICAgICAucmVtb3ZlKCk7XHJcbn0pO1xyXG4kd3JhcHBlcl9ncm91cC5vbignY2xpY2snLCAnLmpzLWFkZC1ncm91cCcsIGZ1bmN0aW9uKGUpIHtcclxuICAgIGUucHJldmVudERlZmF1bHQoKTtcclxuICAgIGxldCBwcm90b3R5cGUgPSAkd3JhcHBlcl9ncm91cC5kYXRhKCdwcm90b3R5cGUnKTtcclxuICAgIGxldCBpbmRleCA9ICR3cmFwcGVyX2dyb3VwLmRhdGEoJ2luZGV4Jyk7XHJcbiAgICBsZXQgbmV3Rm9ybSA9IHByb3RvdHlwZS5yZXBsYWNlKC9fX25hbWVfXy9nLCBpbmRleCk7XHJcbiAgICAkd3JhcHBlcl9ncm91cC5kYXRhKCdpbmRleCcsIGluZGV4ICsgMSk7XHJcbiAgICAkKCcjZ3JvdXA+dWwnKS5hcHBlbmQobmV3Rm9ybSk7XHJcbiAgICBGb3VuZGF0aW9uLnJlSW5pdCgnYWNjb3JkaW9uJyk7XHJcbn0pO1xyXG5cclxuLy8gR3J1cHBlbiAtIE1vZHVsZVxyXG5sZXQgJHdyYXBwZXJfdXNlX21vZHVsZSA9ICQoJy5qcy11c2UtbW9kdWxlLXdyYXBwZXInKTtcclxuJHdyYXBwZXJfdXNlX21vZHVsZS5vbignY2xpY2snLCAnLmpzLXJlbW92ZS11c2UtbW9kdWxlJywgZnVuY3Rpb24oZSkge1xyXG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xyXG4gICAgJCh0aGlzKS5jbG9zZXN0KCcuanMtdXNlLW1vZHVsZS1pdGVtJylcclxuICAgICAgICAucmVtb3ZlKCk7XHJcbn0pO1xyXG4kd3JhcHBlcl91c2VfbW9kdWxlLm9uKCdjbGljaycsICcuanMtYWRkLXVzZS1tb2R1bGUnLCBmdW5jdGlvbihlKSB7XHJcbiAgICBlLnByZXZlbnREZWZhdWx0KCk7XHJcbiAgICBsZXQgcHJvdG90eXBlID0gJHdyYXBwZXJfdXNlX21vZHVsZS5kYXRhKCdwcm90b3R5cGUnKTtcclxuICAgIGxldCBpbmRleCA9ICR3cmFwcGVyX3VzZV9tb2R1bGUuZGF0YSgnaW5kZXgnKTtcclxuICAgIGxldCBncm91cElkID0gZS5jdXJyZW50VGFyZ2V0LmRhdGFzZXQuZ3JvdXBpZDtcclxuICAgIGxldCBuZXdGb3JtID0gcHJvdG90eXBlLnJlcGxhY2UoL19fbmFtZV9fL2csIGluZGV4KS5yZXBsYWNlKC9fZ3JvdXBzXzAvZywgJ19ncm91cHNfJysoZ3JvdXBJZC0xKSkucmVwbGFjZSgvXFxbZ3JvdXBzXFxdXFxbMFxcXS9nLCAnXFxbZ3JvdXBzXFxdXFxbJysoZ3JvdXBJZC0xKSsnXFxdJyk7XHJcbiAgICAkd3JhcHBlcl91c2VfbW9kdWxlLmRhdGEoJ2luZGV4JywgaW5kZXggKyAxKTtcclxuICAgICQoXCIjdXNlLW1vZHVsZXMtXCIrZ3JvdXBJZCtcIj50Ym9keVwiKS5hcHBlbmQobmV3Rm9ybSk7XHJcbn0pO1xyXG5cclxuXHJcbi8vIEdydXBwZW4gLSBNb25hdGVcclxubGV0ICR3cmFwcGVyX21vbnRoID0gJCgnLmpzLW1vbnRoLXdyYXBwZXInKTtcclxuJHdyYXBwZXJfbW9udGgub24oJ2NsaWNrJywgJy5qcy1yZW1vdmUtbW9udGgnLCBmdW5jdGlvbihlKSB7XHJcbiAgICBlLnByZXZlbnREZWZhdWx0KCk7XHJcbiAgICAkKHRoaXMpLmNsb3Nlc3QoJy5qcy1tb250aC1pdGVtJylcclxuICAgICAgICAucmVtb3ZlKCk7XHJcbn0pO1xyXG4kd3JhcHBlcl9tb250aC5vbignY2xpY2snLCAnLmpzLWFkZC1tb250aCcsIGZ1bmN0aW9uKGUpIHtcclxuICAgIGUucHJldmVudERlZmF1bHQoKTtcclxuICAgIGxldCBwcm90b3R5cGUgPSAkd3JhcHBlcl9tb250aC5kYXRhKCdwcm90b3R5cGUnKTtcclxuICAgIGxldCBpbmRleCA9ICR3cmFwcGVyX21vbnRoLmRhdGEoJ2luZGV4Jyk7XHJcbiAgICBsZXQgZ3JvdXBJZCA9IGUuY3VycmVudFRhcmdldC5kYXRhc2V0Lmdyb3VwaWQ7XHJcbiAgICBsZXQgbmV3Rm9ybSA9IHByb3RvdHlwZS5yZXBsYWNlKC9fX25hbWVfXy9nLCBpbmRleCkucmVwbGFjZSgvX2dyb3Vwc18wL2csICdfZ3JvdXBzXycrKGdyb3VwSWQtMSkpLnJlcGxhY2UoL1xcW2dyb3Vwc1xcXVxcWzBcXF0vZywgJ1xcW2dyb3Vwc1xcXVxcWycrKGdyb3VwSWQtMSkrJ1xcXScpO1xyXG4gICAgJHdyYXBwZXJfbW9udGguZGF0YSgnaW5kZXgnLCBpbmRleCArIDEpO1xyXG4gICAgJCgnI21vbnRocy0nK2dyb3VwSWQrJz50Ym9keScpLmFwcGVuZChuZXdGb3JtKTtcclxufSk7XHJcblxyXG4vLyBBbmxhZ2VuIC0gTW9uYXRlXHJcbmxldCAkd3JhcHBlcl9wbGFudF9tb250aCA9ICQoJy5qcy1wbGFudC1tb250aC13cmFwcGVyJyk7XHJcbiR3cmFwcGVyX3BsYW50X21vbnRoLm9uKCdjbGljaycsICcuanMtcmVtb3ZlLXBsYW50LW1vbnRoJywgZnVuY3Rpb24oZSkge1xyXG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xyXG4gICAgJCh0aGlzKS5jbG9zZXN0KCcuanMtcGxhbnQtbW9udGgtaXRlbScpXHJcbiAgICAgICAgLnJlbW92ZSgpO1xyXG59KTtcclxuJHdyYXBwZXJfcGxhbnRfbW9udGgub24oJ2NsaWNrJywgJy5qcy1hZGQtcGxhbnQtbW9udGgnLCBmdW5jdGlvbihlKSB7XHJcbiAgICBlLnByZXZlbnREZWZhdWx0KCk7XHJcbiAgICBsZXQgcHJvdG90eXBlID0gJHdyYXBwZXJfcGxhbnRfbW9udGguZGF0YSgncHJvdG90eXBlJyk7XHJcbiAgICBsZXQgaW5kZXggPSAkd3JhcHBlcl9wbGFudF9tb250aC5kYXRhKCdpbmRleCcpO1xyXG4gICAgbGV0IG5ld0Zvcm0gPSBwcm90b3R5cGUucmVwbGFjZSgvX19uYW1lX18vZywgaW5kZXgpO1xyXG4gICAgJHdyYXBwZXJfcGxhbnRfbW9udGguZGF0YSgnaW5kZXgnLCBpbmRleCArIDEpO1xyXG4gICAgJCgnI3BsYW50LW1vbnRocz50Ym9keScpLmFwcGVuZChuZXdGb3JtKTtcclxufSk7XHJcblxyXG4vLyBBQyBHcnVwcGVcclxubGV0ICR3cmFwcGVyX2FjZ3JvdXAgPSAkKCcuanMtYWNncm91cC13cmFwcGVyJyk7XHJcbiR3cmFwcGVyX2FjZ3JvdXAub24oJ2NsaWNrJywgJy5qcy1yZW1vdmUtYWNncm91cCcsIGZ1bmN0aW9uKGUpIHtcclxuICAgIGUucHJldmVudERlZmF1bHQoKTtcclxuICAgICQodGhpcykuY2xvc2VzdCgnLmpzLWFjZ3JvdXAtaXRlbScpXHJcbiAgICAgICAgLnJlbW92ZSgpO1xyXG59KTtcclxuJHdyYXBwZXJfYWNncm91cC5vbignY2xpY2snLCAnLmpzLWFkZC1hY2dyb3VwJywgZnVuY3Rpb24oZSkge1xyXG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xyXG4gICAgbGV0IHByb3RvdHlwZSA9ICR3cmFwcGVyX2FjZ3JvdXAuZGF0YSgncHJvdG90eXBlJyk7XHJcbiAgICBsZXQgaW5kZXggPSAkd3JhcHBlcl9hY2dyb3VwLmRhdGEoJ2luZGV4Jyk7XHJcbiAgICBsZXQgbmV3Rm9ybSA9IHByb3RvdHlwZS5yZXBsYWNlKC9fX25hbWVfXy9nLCBpbmRleCk7XHJcbiAgICAkd3JhcHBlcl9hY2dyb3VwLmRhdGEoJ2luZGV4JywgaW5kZXggKyAxKTtcclxuICAgICQoJyNqcy1hY2dyb3VwPnRib2R5JykuYXBwZW5kKG5ld0Zvcm0pO1xyXG4gICAgRm91bmRhdGlvbi5yZUluaXQoJ2FjY29yZGlvbicpO1xyXG59KTtcclxuXHJcbiIsIi8vIFRoZSBtb2R1bGUgY2FjaGVcbnZhciBfX3dlYnBhY2tfbW9kdWxlX2NhY2hlX18gPSB7fTtcblxuLy8gVGhlIHJlcXVpcmUgZnVuY3Rpb25cbmZ1bmN0aW9uIF9fd2VicGFja19yZXF1aXJlX18obW9kdWxlSWQpIHtcblx0Ly8gQ2hlY2sgaWYgbW9kdWxlIGlzIGluIGNhY2hlXG5cdHZhciBjYWNoZWRNb2R1bGUgPSBfX3dlYnBhY2tfbW9kdWxlX2NhY2hlX19bbW9kdWxlSWRdO1xuXHRpZiAoY2FjaGVkTW9kdWxlICE9PSB1bmRlZmluZWQpIHtcblx0XHRyZXR1cm4gY2FjaGVkTW9kdWxlLmV4cG9ydHM7XG5cdH1cblx0Ly8gQ3JlYXRlIGEgbmV3IG1vZHVsZSAoYW5kIHB1dCBpdCBpbnRvIHRoZSBjYWNoZSlcblx0dmFyIG1vZHVsZSA9IF9fd2VicGFja19tb2R1bGVfY2FjaGVfX1ttb2R1bGVJZF0gPSB7XG5cdFx0Ly8gbm8gbW9kdWxlLmlkIG5lZWRlZFxuXHRcdC8vIG5vIG1vZHVsZS5sb2FkZWQgbmVlZGVkXG5cdFx0ZXhwb3J0czoge31cblx0fTtcblxuXHQvLyBFeGVjdXRlIHRoZSBtb2R1bGUgZnVuY3Rpb25cblx0X193ZWJwYWNrX21vZHVsZXNfX1ttb2R1bGVJZF0uY2FsbChtb2R1bGUuZXhwb3J0cywgbW9kdWxlLCBtb2R1bGUuZXhwb3J0cywgX193ZWJwYWNrX3JlcXVpcmVfXyk7XG5cblx0Ly8gUmV0dXJuIHRoZSBleHBvcnRzIG9mIHRoZSBtb2R1bGVcblx0cmV0dXJuIG1vZHVsZS5leHBvcnRzO1xufVxuXG4vLyBleHBvc2UgdGhlIG1vZHVsZXMgb2JqZWN0IChfX3dlYnBhY2tfbW9kdWxlc19fKVxuX193ZWJwYWNrX3JlcXVpcmVfXy5tID0gX193ZWJwYWNrX21vZHVsZXNfXztcblxuIiwidmFyIGRlZmVycmVkID0gW107XG5fX3dlYnBhY2tfcmVxdWlyZV9fLk8gPSAocmVzdWx0LCBjaHVua0lkcywgZm4sIHByaW9yaXR5KSA9PiB7XG5cdGlmKGNodW5rSWRzKSB7XG5cdFx0cHJpb3JpdHkgPSBwcmlvcml0eSB8fCAwO1xuXHRcdGZvcih2YXIgaSA9IGRlZmVycmVkLmxlbmd0aDsgaSA+IDAgJiYgZGVmZXJyZWRbaSAtIDFdWzJdID4gcHJpb3JpdHk7IGktLSkgZGVmZXJyZWRbaV0gPSBkZWZlcnJlZFtpIC0gMV07XG5cdFx0ZGVmZXJyZWRbaV0gPSBbY2h1bmtJZHMsIGZuLCBwcmlvcml0eV07XG5cdFx0cmV0dXJuO1xuXHR9XG5cdHZhciBub3RGdWxmaWxsZWQgPSBJbmZpbml0eTtcblx0Zm9yICh2YXIgaSA9IDA7IGkgPCBkZWZlcnJlZC5sZW5ndGg7IGkrKykge1xuXHRcdHZhciBbY2h1bmtJZHMsIGZuLCBwcmlvcml0eV0gPSBkZWZlcnJlZFtpXTtcblx0XHR2YXIgZnVsZmlsbGVkID0gdHJ1ZTtcblx0XHRmb3IgKHZhciBqID0gMDsgaiA8IGNodW5rSWRzLmxlbmd0aDsgaisrKSB7XG5cdFx0XHRpZiAoKHByaW9yaXR5ICYgMSA9PT0gMCB8fCBub3RGdWxmaWxsZWQgPj0gcHJpb3JpdHkpICYmIE9iamVjdC5rZXlzKF9fd2VicGFja19yZXF1aXJlX18uTykuZXZlcnkoKGtleSkgPT4gKF9fd2VicGFja19yZXF1aXJlX18uT1trZXldKGNodW5rSWRzW2pdKSkpKSB7XG5cdFx0XHRcdGNodW5rSWRzLnNwbGljZShqLS0sIDEpO1xuXHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0ZnVsZmlsbGVkID0gZmFsc2U7XG5cdFx0XHRcdGlmKHByaW9yaXR5IDwgbm90RnVsZmlsbGVkKSBub3RGdWxmaWxsZWQgPSBwcmlvcml0eTtcblx0XHRcdH1cblx0XHR9XG5cdFx0aWYoZnVsZmlsbGVkKSB7XG5cdFx0XHRkZWZlcnJlZC5zcGxpY2UoaS0tLCAxKVxuXHRcdFx0dmFyIHIgPSBmbigpO1xuXHRcdFx0aWYgKHIgIT09IHVuZGVmaW5lZCkgcmVzdWx0ID0gcjtcblx0XHR9XG5cdH1cblx0cmV0dXJuIHJlc3VsdDtcbn07IiwiLy8gZ2V0RGVmYXVsdEV4cG9ydCBmdW5jdGlvbiBmb3IgY29tcGF0aWJpbGl0eSB3aXRoIG5vbi1oYXJtb255IG1vZHVsZXNcbl9fd2VicGFja19yZXF1aXJlX18ubiA9IChtb2R1bGUpID0+IHtcblx0dmFyIGdldHRlciA9IG1vZHVsZSAmJiBtb2R1bGUuX19lc01vZHVsZSA/XG5cdFx0KCkgPT4gKG1vZHVsZVsnZGVmYXVsdCddKSA6XG5cdFx0KCkgPT4gKG1vZHVsZSk7XG5cdF9fd2VicGFja19yZXF1aXJlX18uZChnZXR0ZXIsIHsgYTogZ2V0dGVyIH0pO1xuXHRyZXR1cm4gZ2V0dGVyO1xufTsiLCIvLyBkZWZpbmUgZ2V0dGVyIGZ1bmN0aW9ucyBmb3IgaGFybW9ueSBleHBvcnRzXG5fX3dlYnBhY2tfcmVxdWlyZV9fLmQgPSAoZXhwb3J0cywgZGVmaW5pdGlvbikgPT4ge1xuXHRmb3IodmFyIGtleSBpbiBkZWZpbml0aW9uKSB7XG5cdFx0aWYoX193ZWJwYWNrX3JlcXVpcmVfXy5vKGRlZmluaXRpb24sIGtleSkgJiYgIV9fd2VicGFja19yZXF1aXJlX18ubyhleHBvcnRzLCBrZXkpKSB7XG5cdFx0XHRPYmplY3QuZGVmaW5lUHJvcGVydHkoZXhwb3J0cywga2V5LCB7IGVudW1lcmFibGU6IHRydWUsIGdldDogZGVmaW5pdGlvbltrZXldIH0pO1xuXHRcdH1cblx0fVxufTsiLCJfX3dlYnBhY2tfcmVxdWlyZV9fLm8gPSAob2JqLCBwcm9wKSA9PiAoT2JqZWN0LnByb3RvdHlwZS5oYXNPd25Qcm9wZXJ0eS5jYWxsKG9iaiwgcHJvcCkpIiwiLy8gZGVmaW5lIF9fZXNNb2R1bGUgb24gZXhwb3J0c1xuX193ZWJwYWNrX3JlcXVpcmVfXy5yID0gKGV4cG9ydHMpID0+IHtcblx0aWYodHlwZW9mIFN5bWJvbCAhPT0gJ3VuZGVmaW5lZCcgJiYgU3ltYm9sLnRvU3RyaW5nVGFnKSB7XG5cdFx0T2JqZWN0LmRlZmluZVByb3BlcnR5KGV4cG9ydHMsIFN5bWJvbC50b1N0cmluZ1RhZywgeyB2YWx1ZTogJ01vZHVsZScgfSk7XG5cdH1cblx0T2JqZWN0LmRlZmluZVByb3BlcnR5KGV4cG9ydHMsICdfX2VzTW9kdWxlJywgeyB2YWx1ZTogdHJ1ZSB9KTtcbn07IiwiLy8gbm8gYmFzZVVSSVxuXG4vLyBvYmplY3QgdG8gc3RvcmUgbG9hZGVkIGFuZCBsb2FkaW5nIGNodW5rc1xuLy8gdW5kZWZpbmVkID0gY2h1bmsgbm90IGxvYWRlZCwgbnVsbCA9IGNodW5rIHByZWxvYWRlZC9wcmVmZXRjaGVkXG4vLyBbcmVzb2x2ZSwgcmVqZWN0LCBQcm9taXNlXSA9IGNodW5rIGxvYWRpbmcsIDAgPSBjaHVuayBsb2FkZWRcbnZhciBpbnN0YWxsZWRDaHVua3MgPSB7XG5cdFwicGxhbnRfZWRpdFwiOiAwXG59O1xuXG4vLyBubyBjaHVuayBvbiBkZW1hbmQgbG9hZGluZ1xuXG4vLyBubyBwcmVmZXRjaGluZ1xuXG4vLyBubyBwcmVsb2FkZWRcblxuLy8gbm8gSE1SXG5cbi8vIG5vIEhNUiBtYW5pZmVzdFxuXG5fX3dlYnBhY2tfcmVxdWlyZV9fLk8uaiA9IChjaHVua0lkKSA9PiAoaW5zdGFsbGVkQ2h1bmtzW2NodW5rSWRdID09PSAwKTtcblxuLy8gaW5zdGFsbCBhIEpTT05QIGNhbGxiYWNrIGZvciBjaHVuayBsb2FkaW5nXG52YXIgd2VicGFja0pzb25wQ2FsbGJhY2sgPSAocGFyZW50Q2h1bmtMb2FkaW5nRnVuY3Rpb24sIGRhdGEpID0+IHtcblx0dmFyIFtjaHVua0lkcywgbW9yZU1vZHVsZXMsIHJ1bnRpbWVdID0gZGF0YTtcblx0Ly8gYWRkIFwibW9yZU1vZHVsZXNcIiB0byB0aGUgbW9kdWxlcyBvYmplY3QsXG5cdC8vIHRoZW4gZmxhZyBhbGwgXCJjaHVua0lkc1wiIGFzIGxvYWRlZCBhbmQgZmlyZSBjYWxsYmFja1xuXHR2YXIgbW9kdWxlSWQsIGNodW5rSWQsIGkgPSAwO1xuXHRpZihjaHVua0lkcy5zb21lKChpZCkgPT4gKGluc3RhbGxlZENodW5rc1tpZF0gIT09IDApKSkge1xuXHRcdGZvcihtb2R1bGVJZCBpbiBtb3JlTW9kdWxlcykge1xuXHRcdFx0aWYoX193ZWJwYWNrX3JlcXVpcmVfXy5vKG1vcmVNb2R1bGVzLCBtb2R1bGVJZCkpIHtcblx0XHRcdFx0X193ZWJwYWNrX3JlcXVpcmVfXy5tW21vZHVsZUlkXSA9IG1vcmVNb2R1bGVzW21vZHVsZUlkXTtcblx0XHRcdH1cblx0XHR9XG5cdFx0aWYocnVudGltZSkgdmFyIHJlc3VsdCA9IHJ1bnRpbWUoX193ZWJwYWNrX3JlcXVpcmVfXyk7XG5cdH1cblx0aWYocGFyZW50Q2h1bmtMb2FkaW5nRnVuY3Rpb24pIHBhcmVudENodW5rTG9hZGluZ0Z1bmN0aW9uKGRhdGEpO1xuXHRmb3IoO2kgPCBjaHVua0lkcy5sZW5ndGg7IGkrKykge1xuXHRcdGNodW5rSWQgPSBjaHVua0lkc1tpXTtcblx0XHRpZihfX3dlYnBhY2tfcmVxdWlyZV9fLm8oaW5zdGFsbGVkQ2h1bmtzLCBjaHVua0lkKSAmJiBpbnN0YWxsZWRDaHVua3NbY2h1bmtJZF0pIHtcblx0XHRcdGluc3RhbGxlZENodW5rc1tjaHVua0lkXVswXSgpO1xuXHRcdH1cblx0XHRpbnN0YWxsZWRDaHVua3NbY2h1bmtJZF0gPSAwO1xuXHR9XG5cdHJldHVybiBfX3dlYnBhY2tfcmVxdWlyZV9fLk8ocmVzdWx0KTtcbn1cblxudmFyIGNodW5rTG9hZGluZ0dsb2JhbCA9IGdsb2JhbFRoaXNbXCJ3ZWJwYWNrQ2h1bmtcIl0gPSBnbG9iYWxUaGlzW1wid2VicGFja0NodW5rXCJdIHx8IFtdO1xuY2h1bmtMb2FkaW5nR2xvYmFsLmZvckVhY2god2VicGFja0pzb25wQ2FsbGJhY2suYmluZChudWxsLCAwKSk7XG5jaHVua0xvYWRpbmdHbG9iYWwucHVzaCA9IHdlYnBhY2tKc29ucENhbGxiYWNrLmJpbmQobnVsbCwgY2h1bmtMb2FkaW5nR2xvYmFsLnB1c2guYmluZChjaHVua0xvYWRpbmdHbG9iYWwpKTsiLCIiLCIvLyBzdGFydHVwXG4vLyBMb2FkIGVudHJ5IG1vZHVsZSBhbmQgcmV0dXJuIGV4cG9ydHNcbi8vIFRoaXMgZW50cnkgbW9kdWxlIGRlcGVuZHMgb24gb3RoZXIgbG9hZGVkIGNodW5rcyBhbmQgZXhlY3V0aW9uIG5lZWQgdG8gYmUgZGVsYXllZFxudmFyIF9fd2VicGFja19leHBvcnRzX18gPSBfX3dlYnBhY2tfcmVxdWlyZV9fLk8odW5kZWZpbmVkLCBbXCJ2ZW5kb3JzLW5vZGVfbW9kdWxlc19qcXVlcnlfZGlzdF9qcXVlcnlfanNcIl0sICgpID0+IChfX3dlYnBhY2tfcmVxdWlyZV9fKFwiLi9hc3NldHMvanMvcGxhbnRfZWRpdC5qc1wiKSkpXG5fX3dlYnBhY2tfZXhwb3J0c19fID0gX193ZWJwYWNrX3JlcXVpcmVfXy5PKF9fd2VicGFja19leHBvcnRzX18pO1xuIiwiIl0sIm5hbWVzIjpbIiQiLCIkd3JhcHBlcl9zZW5zb3JzIiwib24iLCJlIiwicHJldmVudERlZmF1bHQiLCJjbG9zZXN0IiwicmVtb3ZlIiwicHJvdG90eXBlIiwiZGF0YSIsImluZGV4IiwibmV3Rm9ybSIsInJlcGxhY2UiLCJhcHBlbmQiLCJGb3VuZGF0aW9uIiwicmVJbml0IiwiJHdyYXBwZXJfbW9kdWxlIiwiJHdyYXBwZXJfc3Vuc2hhZGluZyIsIlN3YWwiLCJmaXJlIiwidGl0bGUiLCJ0ZXh0IiwiaWNvbiIsInNob3dDYW5jZWxCdXR0b24iLCJjb25maXJtQnV0dG9uQ29sb3IiLCJ0aW1lciIsImNvbmZpcm1CdXR0b25UZXh0IiwiY2FuY2VsQnV0dG9uVGV4dCIsInNob3dDbG9zZUJ1dHRvbiIsImFsbG93T3V0c2lkZUNsaWNrIiwiYWxsb3dFc2NhcGVLZXkiLCJmb2N1c0NvbmZpcm0iLCJ0aGVuIiwicmVzdWx0IiwiaXNDb25maXJtZWQiLCJjbGljayIsImluZGV4cm93IiwiY3BmaWVsZDAiLCJjcGZpZWxkMSIsImNwZmllbGQyIiwiY3BmaWVsZDMiLCJjcGZpZWxkNCIsImNwZmllbGQ1IiwiY3BmaWVsZDYiLCJjcGZpZWxkNyIsImNwZmllbGQ4IiwiY3BmaWVsZDkiLCJjcGZpZWxkMTAiLCJjcGZpZWxkMTEiLCJjcGZpZWxkMTIiLCJjcGZpZWxkMTMiLCJjcGZpZWxkMTQiLCJud2ZpZWxkMCIsIm53ZmllbGQxIiwibndmaWVsZDIiLCJud2ZpZWxkMyIsIm53ZmllbGQ0IiwibndmaWVsZDUiLCJud2ZpZWxkNiIsIm53ZmllbGQ3IiwibndmaWVsZDgiLCJud2ZpZWxkOSIsIm53ZmllbGQxMCIsIm53ZmllbGQxMSIsIm53ZmllbGQxMiIsIm53ZmllbGQxMyIsIm53ZmllbGQxNCIsInZhbCIsIiR3cmFwcGVyX3RpbWVjb25maWciLCIkd3JhcHBlcl9ldmVudG1haWwiLCIkd3JhcHBlcl9sZWdlbmQiLCIkd3JhcHBlcl9lcGMiLCIkd3JhcHBlcl9wdnN5c3QiLCIkd3JhcHBlcl95aWVsZCIsIiR3cmFwcGVyX2Vjb25vbWljIiwiJHdyYXBwZXJfZ3JvdXAiLCIkd3JhcHBlcl91c2VfbW9kdWxlIiwiZ3JvdXBJZCIsImN1cnJlbnRUYXJnZXQiLCJkYXRhc2V0IiwiZ3JvdXBpZCIsIiR3cmFwcGVyX21vbnRoIiwiJHdyYXBwZXJfcGxhbnRfbW9udGgiLCIkd3JhcHBlcl9hY2dyb3VwIl0sInNvdXJjZVJvb3QiOiIifQ==