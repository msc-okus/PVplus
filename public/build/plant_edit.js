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
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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InNvdXJjZXMiOlsid2VicGFjazovLy8uL2Fzc2V0cy9qcy9wbGFudF9lZGl0LmpzIiwid2VicGFjazovLy93ZWJwYWNrL2Jvb3RzdHJhcCIsIndlYnBhY2s6Ly8vd2VicGFjay9ydW50aW1lL2NodW5rIGxvYWRlZCIsIndlYnBhY2s6Ly8vd2VicGFjay9ydW50aW1lL2NvbXBhdCBnZXQgZGVmYXVsdCBleHBvcnQiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svcnVudGltZS9kZWZpbmUgcHJvcGVydHkgZ2V0dGVycyIsIndlYnBhY2s6Ly8vd2VicGFjay9ydW50aW1lL2hhc093blByb3BlcnR5IHNob3J0aGFuZCIsIndlYnBhY2s6Ly8vd2VicGFjay9ydW50aW1lL21ha2UgbmFtZXNwYWNlIG9iamVjdCIsIndlYnBhY2s6Ly8vd2VicGFjay9ydW50aW1lL2pzb25wIGNodW5rIGxvYWRpbmciLCJ3ZWJwYWNrOi8vL3dlYnBhY2svYmVmb3JlLXN0YXJ0dXAiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svc3RhcnR1cCIsIndlYnBhY2s6Ly8vd2VicGFjay9hZnRlci1zdGFydHVwIl0sInNvdXJjZXNDb250ZW50IjpbImltcG9ydCAkIGZyb20gJ2pxdWVyeSc7XG5cbi8vIFNlbnNvcnNcbmxldCAkd3JhcHBlcl9zZW5zb3JzID0gJCgnLmpzLXNlbnNvcnMtd3JhcHBlcicpO1xuJHdyYXBwZXJfc2Vuc29ycy5vbignY2xpY2snLCAnLmpzLXJlbW92ZS1zZW5zb3InLCBmdW5jdGlvbiAoZSkge1xuICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICAkKHRoaXMpLmNsb3Nlc3QoJy5qcy1zZW5zb3ItaXRlbScpXG4gICAgICAgIC5yZW1vdmUoKTtcbn0pO1xuJHdyYXBwZXJfc2Vuc29ycy5vbignY2xpY2snLCAnLmpzLWFkZC1zZW5zb3InLCBmdW5jdGlvbiAoZSkge1xuICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICBsZXQgcHJvdG90eXBlID0gJHdyYXBwZXJfc2Vuc29ycy5kYXRhKCdwcm90b3R5cGUnKTtcbiAgICBsZXQgaW5kZXggPSAkd3JhcHBlcl9zZW5zb3JzLmRhdGEoJ2luZGV4Jyk7XG4gICAgbGV0IG5ld0Zvcm0gPSBwcm90b3R5cGUucmVwbGFjZSgvX19uYW1lX18vZywgaW5kZXgpO1xuICAgICR3cmFwcGVyX3NlbnNvcnMuZGF0YSgnaW5kZXgnLCBpbmRleCArIDEpO1xuICAgICQoJyNqcy1zZW5zb3JzPnRib2R5JykuYXBwZW5kKG5ld0Zvcm0pO1xuICAgIEZvdW5kYXRpb24ucmVJbml0KCdhY2NvcmRpb24nKTtcbn0pO1xuXG4vLyBNb2R1bGVcbmxldCAkd3JhcHBlcl9tb2R1bGUgPSAkKCcuanMtbW9kdWxlLXdyYXBwZXInKTtcbiR3cmFwcGVyX21vZHVsZS5vbignY2xpY2snLCAnLmpzLXJlbW92ZS1tb2R1bGUnLCBmdW5jdGlvbiAoZSkge1xuICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICAkKHRoaXMpLmNsb3Nlc3QoJy5qcy1tb2R1bGUtaXRlbScpXG4gICAgICAgIC5yZW1vdmUoKTtcbn0pO1xuJHdyYXBwZXJfbW9kdWxlLm9uKCdjbGljaycsICcuanMtYWRkLW1vZHVsZScsIGZ1bmN0aW9uIChlKSB7XG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgIGxldCBwcm90b3R5cGUgPSAkd3JhcHBlcl9tb2R1bGUuZGF0YSgncHJvdG90eXBlJyk7XG4gICAgbGV0IGluZGV4ID0gJHdyYXBwZXJfbW9kdWxlLmRhdGEoJ2luZGV4Jyk7XG4gICAgbGV0IG5ld0Zvcm0gPSBwcm90b3R5cGUucmVwbGFjZSgvX19uYW1lX18vZywgaW5kZXgpO1xuICAgICR3cmFwcGVyX21vZHVsZS5kYXRhKCdpbmRleCcsIGluZGV4ICsgMSk7XG4gICAgJCgnI21vZHVsZXM+dWwnKS5hcHBlbmQobmV3Rm9ybSk7XG4gICAgRm91bmRhdGlvbi5yZUluaXQoJ2FjY29yZGlvbicpO1xufSk7XG4vKlxuKiBNUyAgMDgvMjAyM1xuKiBqcXVlcnkgZGVsZXRlIHN1bnNoYWRpbmcgZGF0YSBmcm9tIGZpZWxkc1xuKi9cbi8vIFN1blNoYWRpbmcgV3JhcHBlclxubGV0ICR3cmFwcGVyX3N1bnNoYWRpbmcgPSAkKCcuanMtc3Vuc2hhZGluZy13cmFwcGVyJyk7XG4kd3JhcHBlcl9zdW5zaGFkaW5nLm9uKCdjbGljaycsICcuanMtcmVtb3ZlLXN1bnNoYWRpbmcnLCBmdW5jdGlvbiAoZSkge1xuICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICBTd2FsLmZpcmUoe1xuICAgICAgICB0aXRsZTogXCJBcmUgeW91IHN1cmU/XCIsXG4gICAgICAgIHRleHQ6IFwiWW91IHdhbnQgdG8gZGVsZXRlIGEgc3Vuc2hhZGluZyBNb2RlbCFcIixcbiAgICAgICAgaWNvbjogXCJxdWVzdGlvblwiLFxuICAgICAgICBzaG93Q2FuY2VsQnV0dG9uOiB0cnVlLFxuICAgICAgICBjb25maXJtQnV0dG9uQ29sb3I6IFwiIzEyNjE5NVwiLFxuICAgICAgICB0aW1lcjogODAwMDAsXG4gICAgICAgIGNvbmZpcm1CdXR0b25UZXh0OiBcIlllcywgZG8gaXQhXCIsXG4gICAgICAgIGNhbmNlbEJ1dHRvblRleHQ6IFwiTm8sIGNhbmNlbCBpdCFcIixcbiAgICAgICAgc2hvd0Nsb3NlQnV0dG9uOiB0cnVlLFxuICAgICAgICBhbGxvd091dHNpZGVDbGljazogZmFsc2UsXG4gICAgICAgIGFsbG93RXNjYXBlS2V5OiBmYWxzZSxcbiAgICAgICAgZm9jdXNDb25maXJtOiB0cnVlXG4gICAgfSkudGhlbigocmVzdWx0KSA9PiB7XG4gICAgICAgIGlmIChyZXN1bHQuaXNDb25maXJtZWQpIHtcbiAgICAgICAgICAgJCh0aGlzKS5jbG9zZXN0KCcuanMtc3Vuc2hhZGluZy1pdGVtJykucmVtb3ZlKCk7XG4gICAgICAgIH1cbiAgICB9KTtcblxufSk7XG4vLyBTdW5TaGFkaW5nIEFkZFxuJCgnLmpzLWFkZC1zdW5zaGFkaW5nJykub24oJ2NsaWNrJywgICBmdW5jdGlvbihlKSB7XG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgIGxldCBwcm90b3R5cGUgPSAkd3JhcHBlcl9zdW5zaGFkaW5nLmRhdGEoJ3Byb3RvdHlwZScpO1xuICAgIGxldCBpbmRleCA9ICR3cmFwcGVyX3N1bnNoYWRpbmcuZGF0YSgnaW5kZXgnKTtcbiAgICBsZXQgbmV3Rm9ybSA9IHByb3RvdHlwZS5yZXBsYWNlKC9fX25hbWVfXy9nLCBpbmRleCk7XG4gICAgJHdyYXBwZXJfc3Vuc2hhZGluZy5kYXRhKCdpbmRleCcsIGluZGV4ICsgMSk7XG4gICAgJCgnI3N1bnNoYWRpbmc+dWwnKS5hcHBlbmQobmV3Rm9ybSk7XG4gICAgRm91bmRhdGlvbi5yZUluaXQoJ2FjY29yZGlvbicpO1xufSApO1xuLypcbiogTVMgIDA4LzIwMjNcbioganF1ZXJ5IGNvcHkgc3Vuc2hhZGluZyBkYXRhIGludG8gbmV3IGlucHV0IGZpZWxkc1xuKi9cbi8vIFN1blNoYWRpbmcgQ29weVxuJCgnLmpzLWNvcHktc3Vuc2hhZGluZycpLmNsaWNrKGZ1bmN0aW9uKCkge1xuICAgIC8vIGNvcHkgZGF0YSBmcm9tIHdyYXBwZXJfc3Vuc2hhZGluZ1xuICAgIGxldCBwcm90b3R5cGUgPSAkd3JhcHBlcl9zdW5zaGFkaW5nLmRhdGEoJ3Byb3RvdHlwZScpO1xuICAgIGxldCBpbmRleHJvdyA9ICR3cmFwcGVyX3N1bnNoYWRpbmcuZGF0YSgnaW5kZXgnKSAtMTtcbiAgICAvLyBwcmVkZWZpbmUgdGhlIGlkIHdpdGggW2luZGV4cm93XSBmcm9tIHRvIGNvcHkgdmFsdWVcbiAgICB2YXIgY3BmaWVsZDAgPScjYW5sYWdlX2Zvcm1fYW5sYWdlU3VuU2hhZGluZ18nK2luZGV4cm93KydfZGVzY3JpcHRpb24nO1xuICAgIHZhciBjcGZpZWxkMSA9JyNhbmxhZ2VfZm9ybV9hbmxhZ2VTdW5TaGFkaW5nXycraW5kZXhyb3crJ19tb2RfdGlsdCc7XG4gICAgdmFyIGNwZmllbGQyID0nI2FubGFnZV9mb3JtX2FubGFnZVN1blNoYWRpbmdfJytpbmRleHJvdysnX21vZF9oZWlnaHQnO1xuICAgIHZhciBjcGZpZWxkMyA9JyNhbmxhZ2VfZm9ybV9hbmxhZ2VTdW5TaGFkaW5nXycraW5kZXhyb3crJ19tb2Rfd2lkdGgnO1xuICAgIHZhciBjcGZpZWxkNCA9JyNhbmxhZ2VfZm9ybV9hbmxhZ2VTdW5TaGFkaW5nXycraW5kZXhyb3crJ19tb2RfdGFibGVfaGVpZ2h0JztcbiAgICB2YXIgY3BmaWVsZDUgPScjYW5sYWdlX2Zvcm1fYW5sYWdlU3VuU2hhZGluZ18nK2luZGV4cm93KydfbW9kX3RhYmxlX2Rpc3RhbmNlJztcbiAgICB2YXIgY3BmaWVsZDYgPScjYW5sYWdlX2Zvcm1fYW5sYWdlU3VuU2hhZGluZ18nK2luZGV4cm93KydfZGlzdGFuY2VfYSc7XG4gICAgdmFyIGNwZmllbGQ3ID0nI2FubGFnZV9mb3JtX2FubGFnZVN1blNoYWRpbmdfJytpbmRleHJvdysnX2Rpc3RhbmNlX2InO1xuICAgIHZhciBjcGZpZWxkOCA9JyNhbmxhZ2VfZm9ybV9hbmxhZ2VTdW5TaGFkaW5nXycraW5kZXhyb3crJ19ncm91bmRfc2xvcGUnO1xuICAgIHZhciBjcGZpZWxkOSA9JyNhbmxhZ2VfZm9ybV9hbmxhZ2VTdW5TaGFkaW5nXycraW5kZXhyb3crJ19tb2R1bGVzREInO1xuICAgIHZhciBjcGZpZWxkMTAgPScjYW5sYWdlX2Zvcm1fYW5sYWdlU3VuU2hhZGluZ18nK2luZGV4cm93KydfaGFzX3Jvd19zaGFkaW5nJztcbiAgICB2YXIgY3BmaWVsZDExID0nI2FubGFnZV9mb3JtX2FubGFnZVN1blNoYWRpbmdfJytpbmRleHJvdysnX21vZF9hbGlnbm1lbnQnO1xuICAgIHZhciBjcGZpZWxkMTIgPScjYW5sYWdlX2Zvcm1fYW5sYWdlU3VuU2hhZGluZ18nK2luZGV4cm93KydfbW9kX2xvbmdfcGFnZSc7XG4gICAgdmFyIGNwZmllbGQxMyA9JyNhbmxhZ2VfZm9ybV9hbmxhZ2VTdW5TaGFkaW5nXycraW5kZXhyb3crJ19tb2Rfc2hvcnRfcGFnZSc7XG4gICAgdmFyIGNwZmllbGQxNCA9JyNhbmxhZ2VfZm9ybV9hbmxhZ2VTdW5TaGFkaW5nXycraW5kZXhyb3crJ19tb2Rfcm93X3RhYmxlcyc7XG4gICAgLy8gYnVpbGQgdGhlIG5ldyB3cmFwcGVyXG4gICAgbGV0IGluZGV4ID0gJHdyYXBwZXJfc3Vuc2hhZGluZy5kYXRhKCdpbmRleCcpO1xuICAgIGxldCBuZXdGb3JtID0gcHJvdG90eXBlLnJlcGxhY2UoL19fbmFtZV9fL2csIGluZGV4KTtcbiAgICAkd3JhcHBlcl9zdW5zaGFkaW5nLmRhdGEoJ2luZGV4JywgaW5kZXggKyAxKTtcbiAgICAkKCcjc3Vuc2hhZGluZz51bCcpLmFwcGVuZChuZXdGb3JtKTtcbiAgICBpbmRleHJvdyA9IGluZGV4cm93ICsgMTtcbiAgICAvLyBwcmVkZWZpbmUgdGhlIGluc2VydCBpZCBbaW5kZXhyb3ddIG9mIHZhbHVlXG4gICAgdmFyIG53ZmllbGQwID0nI2FubGFnZV9mb3JtX2FubGFnZVN1blNoYWRpbmdfJytpbmRleHJvdysnX2Rlc2NyaXB0aW9uJztcbiAgICB2YXIgbndmaWVsZDEgPScjYW5sYWdlX2Zvcm1fYW5sYWdlU3VuU2hhZGluZ18nK2luZGV4cm93KydfbW9kX3RpbHQnO1xuICAgIHZhciBud2ZpZWxkMiA9JyNhbmxhZ2VfZm9ybV9hbmxhZ2VTdW5TaGFkaW5nXycraW5kZXhyb3crJ19tb2RfaGVpZ2h0JztcbiAgICB2YXIgbndmaWVsZDMgPScjYW5sYWdlX2Zvcm1fYW5sYWdlU3VuU2hhZGluZ18nK2luZGV4cm93KydfbW9kX3dpZHRoJztcbiAgICB2YXIgbndmaWVsZDQgPScjYW5sYWdlX2Zvcm1fYW5sYWdlU3VuU2hhZGluZ18nK2luZGV4cm93KydfbW9kX3RhYmxlX2hlaWdodCc7XG4gICAgdmFyIG53ZmllbGQ1ID0nI2FubGFnZV9mb3JtX2FubGFnZVN1blNoYWRpbmdfJytpbmRleHJvdysnX21vZF90YWJsZV9kaXN0YW5jZSc7XG4gICAgdmFyIG53ZmllbGQ2ID0nI2FubGFnZV9mb3JtX2FubGFnZVN1blNoYWRpbmdfJytpbmRleHJvdysnX2Rpc3RhbmNlX2EnO1xuICAgIHZhciBud2ZpZWxkNyA9JyNhbmxhZ2VfZm9ybV9hbmxhZ2VTdW5TaGFkaW5nXycraW5kZXhyb3crJ19kaXN0YW5jZV9iJztcbiAgICB2YXIgbndmaWVsZDggPScjYW5sYWdlX2Zvcm1fYW5sYWdlU3VuU2hhZGluZ18nK2luZGV4cm93KydfZ3JvdW5kX3Nsb3BlJztcbiAgICB2YXIgbndmaWVsZDkgPScjYW5sYWdlX2Zvcm1fYW5sYWdlU3VuU2hhZGluZ18nK2luZGV4cm93KydfbW9kdWxlc0RCJztcbiAgICB2YXIgbndmaWVsZDEwID0nI2FubGFnZV9mb3JtX2FubGFnZVN1blNoYWRpbmdfJytpbmRleHJvdysnX2hhc19yb3dfc2hhZGluZyc7XG4gICAgdmFyIG53ZmllbGQxMSA9JyNhbmxhZ2VfZm9ybV9hbmxhZ2VTdW5TaGFkaW5nXycraW5kZXhyb3crJ19tb2RfYWxpZ25tZW50JztcbiAgICB2YXIgbndmaWVsZDEyID0nI2FubGFnZV9mb3JtX2FubGFnZVN1blNoYWRpbmdfJytpbmRleHJvdysnX21vZF9sb25nX3BhZ2UnO1xuICAgIHZhciBud2ZpZWxkMTMgPScjYW5sYWdlX2Zvcm1fYW5sYWdlU3VuU2hhZGluZ18nK2luZGV4cm93KydfbW9kX3Nob3J0X3BhZ2UnO1xuICAgIHZhciBud2ZpZWxkMTQgPScjYW5sYWdlX2Zvcm1fYW5sYWdlU3VuU2hhZGluZ18nK2luZGV4cm93KydfbW9kX3Jvd190YWJsZXMnO1xuICAgIC8vIGJlZ2luIGNvcHlcbiAgICAkKG53ZmllbGQwKS52YWwoJChjcGZpZWxkMCkudmFsKCkpO1xuICAgICQobndmaWVsZDEpLnZhbCgkKGNwZmllbGQxKS52YWwoKSk7XG4gICAgJChud2ZpZWxkMikudmFsKCQoY3BmaWVsZDIpLnZhbCgpKTtcbiAgICAkKG53ZmllbGQzKS52YWwoJChjcGZpZWxkMykudmFsKCkpO1xuICAgICQobndmaWVsZDQpLnZhbCgkKGNwZmllbGQ0KS52YWwoKSk7XG4gICAgJChud2ZpZWxkNSkudmFsKCQoY3BmaWVsZDUpLnZhbCgpKTtcbiAgICAkKG53ZmllbGQ2KS52YWwoJChjcGZpZWxkNikudmFsKCkpO1xuICAgICQobndmaWVsZDcpLnZhbCgkKGNwZmllbGQ3KS52YWwoKSk7XG4gICAgJChud2ZpZWxkOCkudmFsKCQoY3BmaWVsZDgpLnZhbCgpKTtcbiAgICAkKG53ZmllbGQ5KS52YWwoJChjcGZpZWxkOSkudmFsKCkpO1xuICAgICQobndmaWVsZDEwKS52YWwoJChjcGZpZWxkMTApLnZhbCgpKTtcbiAgICAkKG53ZmllbGQxMSkudmFsKCQoY3BmaWVsZDExKS52YWwoKSk7XG4gICAgJChud2ZpZWxkMTIpLnZhbCgkKGNwZmllbGQxMikudmFsKCkpO1xuICAgICQobndmaWVsZDEzKS52YWwoJChjcGZpZWxkMTMpLnZhbCgpKTtcbiAgICAkKG53ZmllbGQxNCkudmFsKCQoY3BmaWVsZDE0KS52YWwoKSk7XG4gICAgLy8gZW5kZSBjb3B5IGFuZCByZWluaXR6aWFsIGFjY29yZGlvblxuICAgICQoJyNhY2NvcmRpb24tdGl0bGUnKS50ZXh0KCdORVcgU3VuIFNoYWRpbmcgTW9kZWwgZnJvbSBhIENPUFk6Jyk7XG4gICAgRm91bmRhdGlvbi5yZUluaXQoJ2FjY29yZGlvbicpO1xufSApO1xuXG4vLyB0aGUgVGltZSBDb25maWcgd3JhcHBlclxubGV0ICR3cmFwcGVyX3RpbWVjb25maWcgPSAkKCcuanMtdGltZUNvbmZpZy13cmFwcGVyJyk7XG4kd3JhcHBlcl90aW1lY29uZmlnLm9uKCdjbGljaycsICcuanMtcmVtb3ZlLXRpbWVDb25maWctbW9kdWxlJywgZnVuY3Rpb24gKGUpIHtcbiAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgJCh0aGlzKS5jbG9zZXN0KCcuanMtdGltZUNvbmZpZy1pdGVtJylcbiAgICAgICAgLnJlbW92ZSgpO1xufSk7XG4kd3JhcHBlcl90aW1lY29uZmlnLm9uKCdjbGljaycsICcuanMtYWRkLXRpbWVDb25maWcnLCBmdW5jdGlvbiAoZSkge1xuICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICBsZXQgcHJvdG90eXBlID0gJHdyYXBwZXJfdGltZWNvbmZpZy5kYXRhKCdwcm90b3R5cGUnKTtcbiAgICBsZXQgaW5kZXggPSAkd3JhcHBlcl90aW1lY29uZmlnLmRhdGEoJ2luZGV4Jyk7XG4gICAgbGV0IG5ld0Zvcm0gPSBwcm90b3R5cGUucmVwbGFjZSgvX19uYW1lX18vZywgaW5kZXgpO1xuICAgICR3cmFwcGVyX3RpbWVjb25maWcuZGF0YSgnaW5kZXgnLCBpbmRleCArIDEpO1xuICAgICQoJyN0aW1lQ29uZmlnPnRib2R5JykuYXBwZW5kKG5ld0Zvcm0pO1xufSk7XG5cbi8vIEV2ZW50IE1haWxcbmxldCAkd3JhcHBlcl9ldmVudG1haWwgPSAkKCcuanMtZXZlbnRtYWlsLXdyYXBwZXInKTtcbiR3cmFwcGVyX2V2ZW50bWFpbC5vbignY2xpY2snLCAnLmpzLXJlbW92ZS1ldmVudG1haWwnLCBmdW5jdGlvbihlKSB7XG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICQodGhpcykuY2xvc2VzdCgnLmpzLWV2ZW50bWFpbC1pdGVtJylcbiAgICAgICAgLnJlbW92ZSgpO1xufSk7XG4kd3JhcHBlcl9ldmVudG1haWwub24oJ2NsaWNrJywgJy5qcy1hZGQtZXZlbnRtYWlsJywgZnVuY3Rpb24oZSkge1xuICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICBsZXQgcHJvdG90eXBlID0gJHdyYXBwZXJfZXZlbnRtYWlsLmRhdGEoJ3Byb3RvdHlwZScpO1xuICAgIGxldCBpbmRleCA9ICR3cmFwcGVyX2V2ZW50bWFpbC5kYXRhKCdpbmRleCcpO1xuICAgIGxldCBuZXdGb3JtID0gcHJvdG90eXBlLnJlcGxhY2UoL19fbmFtZV9fL2csIGluZGV4KTtcbiAgICAkd3JhcHBlcl9ldmVudG1haWwuZGF0YSgnaW5kZXgnLCBpbmRleCArIDEpO1xuICAgICQoJyNldmVuLW1haWw+dGJvZHknKS5hcHBlbmQobmV3Rm9ybSk7XG59KTtcblxuLy8gbGVnZW5kXG5sZXQgJHdyYXBwZXJfbGVnZW5kID0gJCgnLmpzLWxlZ2VuZC1fbW9udGhseS13cmFwcGVyJyk7XG4kd3JhcHBlcl9sZWdlbmQub24oJ2NsaWNrJywgJy5qcy1yZW1vdmUtbGVnZW5kLV9tb250aGx5JywgZnVuY3Rpb24oZSkge1xuICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICAkKHRoaXMpLmNsb3Nlc3QoJy5qcy1sZWdlbmQtX21vbnRobHktaXRlbScpXG4gICAgICAgIC5yZW1vdmUoKTtcbn0pO1xuJHdyYXBwZXJfbGVnZW5kLm9uKCdjbGljaycsICcuanMtYWRkLWxlZ2VuZC1fbW9udGhseScsIGZ1bmN0aW9uKGUpIHtcbiAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgbGV0IHByb3RvdHlwZSA9ICR3cmFwcGVyX2xlZ2VuZC5kYXRhKCdwcm90b3R5cGUnKTtcbiAgICBsZXQgaW5kZXggPSAkd3JhcHBlcl9sZWdlbmQuZGF0YSgnaW5kZXgnKTtcbiAgICBsZXQgbmV3Rm9ybSA9IHByb3RvdHlwZS5yZXBsYWNlKC9fX25hbWVfXy9nLCBpbmRleCk7XG4gICAgJHdyYXBwZXJfbGVnZW5kLmRhdGEoJ2luZGV4JywgaW5kZXggKyAxKTtcbiAgICAkKCcjbGVnZW5kLV9tb250aGx5PnRib2R5JykuYXBwZW5kKG5ld0Zvcm0pO1xufSk7XG5cbi8vIGxlZ2VuZCBFUENcbmxldCAkd3JhcHBlcl9lcGMgPSAkKCcuanMtbGVnZW5kLWVwYy13cmFwcGVyJyk7XG4kd3JhcHBlcl9lcGMub24oJ2NsaWNrJywgJy5qcy1yZW1vdmUtbGVnZW5kLWVwYycsIGZ1bmN0aW9uKGUpIHtcbiAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgJCh0aGlzKS5jbG9zZXN0KCcuanMtbGVnZW5kLWVwYy1pdGVtJylcbiAgICAgICAgLnJlbW92ZSgpO1xufSk7XG4kd3JhcHBlcl9lcGMub24oJ2NsaWNrJywgJy5qcy1hZGQtbGVnZW5kLWVwYycsIGZ1bmN0aW9uKGUpIHtcbiAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgbGV0IHByb3RvdHlwZSA9ICR3cmFwcGVyX2VwYy5kYXRhKCdwcm90b3R5cGUnKTtcbiAgICBsZXQgaW5kZXggPSAkd3JhcHBlcl9lcGMuZGF0YSgnaW5kZXgnKTtcbiAgICBsZXQgbmV3Rm9ybSA9IHByb3RvdHlwZS5yZXBsYWNlKC9fX25hbWVfXy9nLCBpbmRleCk7XG4gICAgJHdyYXBwZXJfZXBjLmRhdGEoJ2luZGV4JywgaW5kZXggKyAxKTtcbiAgICAkKCcjbGVnZW5kLWVwYz50Ym9keScpLmFwcGVuZChuZXdGb3JtKTtcbn0pO1xuXG4vLyBwdnN5c3QgRGVzaWduIFdlcnRlXG5sZXQgJHdyYXBwZXJfcHZzeXN0ID0gJCgnLmpzLXB2c3lzdC13cmFwcGVyJyk7XG4kd3JhcHBlcl9wdnN5c3Qub24oJ2NsaWNrJywgJy5qcy1yZW1vdmUtcHZzeXN0JywgZnVuY3Rpb24oZSkge1xuICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICAkKHRoaXMpLmNsb3Nlc3QoJy5qcy1wdnN5c3QtaXRlbScpXG4gICAgICAgIC5yZW1vdmUoKTtcbn0pO1xuJHdyYXBwZXJfcHZzeXN0Lm9uKCdjbGljaycsICcuanMtYWRkLXB2c3lzdCcsIGZ1bmN0aW9uKGUpIHtcbiAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgbGV0IHByb3RvdHlwZSA9ICR3cmFwcGVyX3B2c3lzdC5kYXRhKCdwcm90b3R5cGUnKTtcbiAgICBsZXQgaW5kZXggPSAkd3JhcHBlcl9wdnN5c3QuZGF0YSgnaW5kZXgnKTtcbiAgICBsZXQgbmV3Rm9ybSA9IHByb3RvdHlwZS5yZXBsYWNlKC9fX25hbWVfXy9nLCBpbmRleCk7XG4gICAgJHdyYXBwZXJfcHZzeXN0LmRhdGEoJ2luZGV4JywgaW5kZXggKyAxKTtcbiAgICAkKCcjcHZzeXN0LXZhbHVlcz50Ym9keScpLmFwcGVuZChuZXdGb3JtKTtcbn0pO1xuXG4vLyBfbW9udGhseS15aWVsZFxubGV0ICR3cmFwcGVyX3lpZWxkID0gJCgnLmpzLV9tb250aGx5LXlpZWxkLXdyYXBwZXInKTtcbiR3cmFwcGVyX3lpZWxkLm9uKCdjbGljaycsICcuanMtcmVtb3ZlLV9tb250aGx5LXlpZWxkJywgZnVuY3Rpb24oZSkge1xuICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICAkKHRoaXMpLmNsb3Nlc3QoJy5qcy1fbW9udGhseS15aWVsZC1pdGVtJylcbiAgICAgICAgLnJlbW92ZSgpO1xufSk7XG4kd3JhcHBlcl95aWVsZC5vbignY2xpY2snLCAnLmpzLWFkZC1fbW9udGhseS15aWVsZCcsIGZ1bmN0aW9uKGUpIHtcbiAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgbGV0IHByb3RvdHlwZSA9ICR3cmFwcGVyX3lpZWxkLmRhdGEoJ3Byb3RvdHlwZScpO1xuICAgIGxldCBpbmRleCA9ICR3cmFwcGVyX3lpZWxkLmRhdGEoJ2luZGV4Jyk7XG4gICAgbGV0IG5ld0Zvcm0gPSBwcm90b3R5cGUucmVwbGFjZSgvX19uYW1lX18vZywgaW5kZXgpO1xuICAgICR3cmFwcGVyX3lpZWxkLmRhdGEoJ2luZGV4JywgaW5kZXggKyAxKTtcbiAgICAkKCcjX21vbnRobHkteWllbGQtdmFsdWVzPnRib2R5JykuYXBwZW5kKG5ld0Zvcm0pO1xufSk7XG5cbi8vIEVjb25vbWljc1xubGV0ICR3cmFwcGVyX2Vjb25vbWljID0gJCgnLmpzLWVjb25vbWljVmFyVmFsdWVzLXdyYXBwZXInKTtcbiR3cmFwcGVyX2Vjb25vbWljLm9uKCdjbGljaycsICcuanMtZWNvbm9taWMtdmFyLXZhbHVlLWFkZCcsZnVuY3Rpb24oZSl7XG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgIGxldCBwcm90b3R5cGUgPSAkd3JhcHBlcl9lY29ub21pYy5kYXRhKCdwcm90b3R5cGUnKTtcbiAgICBsZXQgaW5kZXggPSAkd3JhcHBlcl9lY29ub21pYy5kYXRhKCdpbmRleCcpO1xuICAgIGxldCBuZXdGb3JtID0gcHJvdG90eXBlLnJlcGxhY2UoL19fbmFtZV9fL2csIGluZGV4KTtcbiAgICAkd3JhcHBlcl9lY29ub21pYy5kYXRhKCdpbmRleCcsIGluZGV4KzEpO1xuICAgICQoJyNlY29ub21pY3N2YWx1ZXMtdmFsdWVzPnRib2R5JykuYXBwZW5kKG5ld0Zvcm0pO1xufSlcblxuLy8gR3J1cHBlblxubGV0ICR3cmFwcGVyX2dyb3VwID0gJCgnLmpzLWdyb3VwLXdyYXBwZXInKTtcbiR3cmFwcGVyX2dyb3VwLm9uKCdjbGljaycsICcuanMtcmVtb3ZlLWdyb3VwJywgZnVuY3Rpb24oZSkge1xuICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICAkKHRoaXMpLmNsb3Nlc3QoJy5qcy1ncm91cC1pdGVtJylcbiAgICAgICAgLnJlbW92ZSgpO1xufSk7XG4kd3JhcHBlcl9ncm91cC5vbignY2xpY2snLCAnLmpzLWFkZC1ncm91cCcsIGZ1bmN0aW9uKGUpIHtcbiAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgbGV0IHByb3RvdHlwZSA9ICR3cmFwcGVyX2dyb3VwLmRhdGEoJ3Byb3RvdHlwZScpO1xuICAgIGxldCBpbmRleCA9ICR3cmFwcGVyX2dyb3VwLmRhdGEoJ2luZGV4Jyk7XG4gICAgbGV0IG5ld0Zvcm0gPSBwcm90b3R5cGUucmVwbGFjZSgvX19uYW1lX18vZywgaW5kZXgpO1xuICAgICR3cmFwcGVyX2dyb3VwLmRhdGEoJ2luZGV4JywgaW5kZXggKyAxKTtcbiAgICAkKCcjZ3JvdXA+dWwnKS5hcHBlbmQobmV3Rm9ybSk7XG4gICAgRm91bmRhdGlvbi5yZUluaXQoJ2FjY29yZGlvbicpO1xufSk7XG5cbi8vIEdydXBwZW4gLSBNb2R1bGVcbmxldCAkd3JhcHBlcl91c2VfbW9kdWxlID0gJCgnLmpzLXVzZS1tb2R1bGUtd3JhcHBlcicpO1xuJHdyYXBwZXJfdXNlX21vZHVsZS5vbignY2xpY2snLCAnLmpzLXJlbW92ZS11c2UtbW9kdWxlJywgZnVuY3Rpb24oZSkge1xuICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICAkKHRoaXMpLmNsb3Nlc3QoJy5qcy11c2UtbW9kdWxlLWl0ZW0nKVxuICAgICAgICAucmVtb3ZlKCk7XG59KTtcbiR3cmFwcGVyX3VzZV9tb2R1bGUub24oJ2NsaWNrJywgJy5qcy1hZGQtdXNlLW1vZHVsZScsIGZ1bmN0aW9uKGUpIHtcbiAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgbGV0IHByb3RvdHlwZSA9ICR3cmFwcGVyX3VzZV9tb2R1bGUuZGF0YSgncHJvdG90eXBlJyk7XG4gICAgbGV0IGluZGV4ID0gJHdyYXBwZXJfdXNlX21vZHVsZS5kYXRhKCdpbmRleCcpO1xuICAgIGxldCBncm91cElkID0gZS5jdXJyZW50VGFyZ2V0LmRhdGFzZXQuZ3JvdXBpZDtcbiAgICBsZXQgbmV3Rm9ybSA9IHByb3RvdHlwZS5yZXBsYWNlKC9fX25hbWVfXy9nLCBpbmRleCkucmVwbGFjZSgvX2dyb3Vwc18wL2csICdfZ3JvdXBzXycrKGdyb3VwSWQtMSkpLnJlcGxhY2UoL1xcW2dyb3Vwc1xcXVxcWzBcXF0vZywgJ1xcW2dyb3Vwc1xcXVxcWycrKGdyb3VwSWQtMSkrJ1xcXScpO1xuICAgICR3cmFwcGVyX3VzZV9tb2R1bGUuZGF0YSgnaW5kZXgnLCBpbmRleCArIDEpO1xuICAgICQoXCIjdXNlLW1vZHVsZXMtXCIrZ3JvdXBJZCtcIj50Ym9keVwiKS5hcHBlbmQobmV3Rm9ybSk7XG59KTtcblxuXG4vLyBHcnVwcGVuIC0gTW9uYXRlXG5sZXQgJHdyYXBwZXJfbW9udGggPSAkKCcuanMtbW9udGgtd3JhcHBlcicpO1xuJHdyYXBwZXJfbW9udGgub24oJ2NsaWNrJywgJy5qcy1yZW1vdmUtbW9udGgnLCBmdW5jdGlvbihlKSB7XG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICQodGhpcykuY2xvc2VzdCgnLmpzLW1vbnRoLWl0ZW0nKVxuICAgICAgICAucmVtb3ZlKCk7XG59KTtcbiR3cmFwcGVyX21vbnRoLm9uKCdjbGljaycsICcuanMtYWRkLW1vbnRoJywgZnVuY3Rpb24oZSkge1xuICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICBsZXQgcHJvdG90eXBlID0gJHdyYXBwZXJfbW9udGguZGF0YSgncHJvdG90eXBlJyk7XG4gICAgbGV0IGluZGV4ID0gJHdyYXBwZXJfbW9udGguZGF0YSgnaW5kZXgnKTtcbiAgICBsZXQgZ3JvdXBJZCA9IGUuY3VycmVudFRhcmdldC5kYXRhc2V0Lmdyb3VwaWQ7XG4gICAgbGV0IG5ld0Zvcm0gPSBwcm90b3R5cGUucmVwbGFjZSgvX19uYW1lX18vZywgaW5kZXgpLnJlcGxhY2UoL19ncm91cHNfMC9nLCAnX2dyb3Vwc18nKyhncm91cElkLTEpKS5yZXBsYWNlKC9cXFtncm91cHNcXF1cXFswXFxdL2csICdcXFtncm91cHNcXF1cXFsnKyhncm91cElkLTEpKydcXF0nKTtcbiAgICAkd3JhcHBlcl9tb250aC5kYXRhKCdpbmRleCcsIGluZGV4ICsgMSk7XG4gICAgJCgnI21vbnRocy0nK2dyb3VwSWQrJz50Ym9keScpLmFwcGVuZChuZXdGb3JtKTtcbn0pO1xuXG4vLyBBbmxhZ2VuIC0gTW9uYXRlXG5sZXQgJHdyYXBwZXJfcGxhbnRfbW9udGggPSAkKCcuanMtcGxhbnQtbW9udGgtd3JhcHBlcicpO1xuJHdyYXBwZXJfcGxhbnRfbW9udGgub24oJ2NsaWNrJywgJy5qcy1yZW1vdmUtcGxhbnQtbW9udGgnLCBmdW5jdGlvbihlKSB7XG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICQodGhpcykuY2xvc2VzdCgnLmpzLXBsYW50LW1vbnRoLWl0ZW0nKVxuICAgICAgICAucmVtb3ZlKCk7XG59KTtcbiR3cmFwcGVyX3BsYW50X21vbnRoLm9uKCdjbGljaycsICcuanMtYWRkLXBsYW50LW1vbnRoJywgZnVuY3Rpb24oZSkge1xuICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICBsZXQgcHJvdG90eXBlID0gJHdyYXBwZXJfcGxhbnRfbW9udGguZGF0YSgncHJvdG90eXBlJyk7XG4gICAgbGV0IGluZGV4ID0gJHdyYXBwZXJfcGxhbnRfbW9udGguZGF0YSgnaW5kZXgnKTtcbiAgICBsZXQgbmV3Rm9ybSA9IHByb3RvdHlwZS5yZXBsYWNlKC9fX25hbWVfXy9nLCBpbmRleCk7XG4gICAgJHdyYXBwZXJfcGxhbnRfbW9udGguZGF0YSgnaW5kZXgnLCBpbmRleCArIDEpO1xuICAgICQoJyNwbGFudC1tb250aHM+dGJvZHknKS5hcHBlbmQobmV3Rm9ybSk7XG59KTtcblxuLy8gQUMgR3J1cHBlXG5sZXQgJHdyYXBwZXJfYWNncm91cCA9ICQoJy5qcy1hY2dyb3VwLXdyYXBwZXInKTtcbiR3cmFwcGVyX2FjZ3JvdXAub24oJ2NsaWNrJywgJy5qcy1yZW1vdmUtYWNncm91cCcsIGZ1bmN0aW9uKGUpIHtcbiAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgJCh0aGlzKS5jbG9zZXN0KCcuanMtYWNncm91cC1pdGVtJylcbiAgICAgICAgLnJlbW92ZSgpO1xufSk7XG4kd3JhcHBlcl9hY2dyb3VwLm9uKCdjbGljaycsICcuanMtYWRkLWFjZ3JvdXAnLCBmdW5jdGlvbihlKSB7XG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgIGxldCBwcm90b3R5cGUgPSAkd3JhcHBlcl9hY2dyb3VwLmRhdGEoJ3Byb3RvdHlwZScpO1xuICAgIGxldCBpbmRleCA9ICR3cmFwcGVyX2FjZ3JvdXAuZGF0YSgnaW5kZXgnKTtcbiAgICBsZXQgbmV3Rm9ybSA9IHByb3RvdHlwZS5yZXBsYWNlKC9fX25hbWVfXy9nLCBpbmRleCk7XG4gICAgJHdyYXBwZXJfYWNncm91cC5kYXRhKCdpbmRleCcsIGluZGV4ICsgMSk7XG4gICAgJCgnI2pzLWFjZ3JvdXA+dGJvZHknKS5hcHBlbmQobmV3Rm9ybSk7XG4gICAgRm91bmRhdGlvbi5yZUluaXQoJ2FjY29yZGlvbicpO1xufSk7XG5cbiIsIi8vIFRoZSBtb2R1bGUgY2FjaGVcbnZhciBfX3dlYnBhY2tfbW9kdWxlX2NhY2hlX18gPSB7fTtcblxuLy8gVGhlIHJlcXVpcmUgZnVuY3Rpb25cbmZ1bmN0aW9uIF9fd2VicGFja19yZXF1aXJlX18obW9kdWxlSWQpIHtcblx0Ly8gQ2hlY2sgaWYgbW9kdWxlIGlzIGluIGNhY2hlXG5cdHZhciBjYWNoZWRNb2R1bGUgPSBfX3dlYnBhY2tfbW9kdWxlX2NhY2hlX19bbW9kdWxlSWRdO1xuXHRpZiAoY2FjaGVkTW9kdWxlICE9PSB1bmRlZmluZWQpIHtcblx0XHRyZXR1cm4gY2FjaGVkTW9kdWxlLmV4cG9ydHM7XG5cdH1cblx0Ly8gQ3JlYXRlIGEgbmV3IG1vZHVsZSAoYW5kIHB1dCBpdCBpbnRvIHRoZSBjYWNoZSlcblx0dmFyIG1vZHVsZSA9IF9fd2VicGFja19tb2R1bGVfY2FjaGVfX1ttb2R1bGVJZF0gPSB7XG5cdFx0Ly8gbm8gbW9kdWxlLmlkIG5lZWRlZFxuXHRcdC8vIG5vIG1vZHVsZS5sb2FkZWQgbmVlZGVkXG5cdFx0ZXhwb3J0czoge31cblx0fTtcblxuXHQvLyBFeGVjdXRlIHRoZSBtb2R1bGUgZnVuY3Rpb25cblx0X193ZWJwYWNrX21vZHVsZXNfX1ttb2R1bGVJZF0uY2FsbChtb2R1bGUuZXhwb3J0cywgbW9kdWxlLCBtb2R1bGUuZXhwb3J0cywgX193ZWJwYWNrX3JlcXVpcmVfXyk7XG5cblx0Ly8gUmV0dXJuIHRoZSBleHBvcnRzIG9mIHRoZSBtb2R1bGVcblx0cmV0dXJuIG1vZHVsZS5leHBvcnRzO1xufVxuXG4vLyBleHBvc2UgdGhlIG1vZHVsZXMgb2JqZWN0IChfX3dlYnBhY2tfbW9kdWxlc19fKVxuX193ZWJwYWNrX3JlcXVpcmVfXy5tID0gX193ZWJwYWNrX21vZHVsZXNfXztcblxuIiwidmFyIGRlZmVycmVkID0gW107XG5fX3dlYnBhY2tfcmVxdWlyZV9fLk8gPSAocmVzdWx0LCBjaHVua0lkcywgZm4sIHByaW9yaXR5KSA9PiB7XG5cdGlmKGNodW5rSWRzKSB7XG5cdFx0cHJpb3JpdHkgPSBwcmlvcml0eSB8fCAwO1xuXHRcdGZvcih2YXIgaSA9IGRlZmVycmVkLmxlbmd0aDsgaSA+IDAgJiYgZGVmZXJyZWRbaSAtIDFdWzJdID4gcHJpb3JpdHk7IGktLSkgZGVmZXJyZWRbaV0gPSBkZWZlcnJlZFtpIC0gMV07XG5cdFx0ZGVmZXJyZWRbaV0gPSBbY2h1bmtJZHMsIGZuLCBwcmlvcml0eV07XG5cdFx0cmV0dXJuO1xuXHR9XG5cdHZhciBub3RGdWxmaWxsZWQgPSBJbmZpbml0eTtcblx0Zm9yICh2YXIgaSA9IDA7IGkgPCBkZWZlcnJlZC5sZW5ndGg7IGkrKykge1xuXHRcdHZhciBbY2h1bmtJZHMsIGZuLCBwcmlvcml0eV0gPSBkZWZlcnJlZFtpXTtcblx0XHR2YXIgZnVsZmlsbGVkID0gdHJ1ZTtcblx0XHRmb3IgKHZhciBqID0gMDsgaiA8IGNodW5rSWRzLmxlbmd0aDsgaisrKSB7XG5cdFx0XHRpZiAoKHByaW9yaXR5ICYgMSA9PT0gMCB8fCBub3RGdWxmaWxsZWQgPj0gcHJpb3JpdHkpICYmIE9iamVjdC5rZXlzKF9fd2VicGFja19yZXF1aXJlX18uTykuZXZlcnkoKGtleSkgPT4gKF9fd2VicGFja19yZXF1aXJlX18uT1trZXldKGNodW5rSWRzW2pdKSkpKSB7XG5cdFx0XHRcdGNodW5rSWRzLnNwbGljZShqLS0sIDEpO1xuXHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0ZnVsZmlsbGVkID0gZmFsc2U7XG5cdFx0XHRcdGlmKHByaW9yaXR5IDwgbm90RnVsZmlsbGVkKSBub3RGdWxmaWxsZWQgPSBwcmlvcml0eTtcblx0XHRcdH1cblx0XHR9XG5cdFx0aWYoZnVsZmlsbGVkKSB7XG5cdFx0XHRkZWZlcnJlZC5zcGxpY2UoaS0tLCAxKVxuXHRcdFx0dmFyIHIgPSBmbigpO1xuXHRcdFx0aWYgKHIgIT09IHVuZGVmaW5lZCkgcmVzdWx0ID0gcjtcblx0XHR9XG5cdH1cblx0cmV0dXJuIHJlc3VsdDtcbn07IiwiLy8gZ2V0RGVmYXVsdEV4cG9ydCBmdW5jdGlvbiBmb3IgY29tcGF0aWJpbGl0eSB3aXRoIG5vbi1oYXJtb255IG1vZHVsZXNcbl9fd2VicGFja19yZXF1aXJlX18ubiA9IChtb2R1bGUpID0+IHtcblx0dmFyIGdldHRlciA9IG1vZHVsZSAmJiBtb2R1bGUuX19lc01vZHVsZSA/XG5cdFx0KCkgPT4gKG1vZHVsZVsnZGVmYXVsdCddKSA6XG5cdFx0KCkgPT4gKG1vZHVsZSk7XG5cdF9fd2VicGFja19yZXF1aXJlX18uZChnZXR0ZXIsIHsgYTogZ2V0dGVyIH0pO1xuXHRyZXR1cm4gZ2V0dGVyO1xufTsiLCIvLyBkZWZpbmUgZ2V0dGVyIGZ1bmN0aW9ucyBmb3IgaGFybW9ueSBleHBvcnRzXG5fX3dlYnBhY2tfcmVxdWlyZV9fLmQgPSAoZXhwb3J0cywgZGVmaW5pdGlvbikgPT4ge1xuXHRmb3IodmFyIGtleSBpbiBkZWZpbml0aW9uKSB7XG5cdFx0aWYoX193ZWJwYWNrX3JlcXVpcmVfXy5vKGRlZmluaXRpb24sIGtleSkgJiYgIV9fd2VicGFja19yZXF1aXJlX18ubyhleHBvcnRzLCBrZXkpKSB7XG5cdFx0XHRPYmplY3QuZGVmaW5lUHJvcGVydHkoZXhwb3J0cywga2V5LCB7IGVudW1lcmFibGU6IHRydWUsIGdldDogZGVmaW5pdGlvbltrZXldIH0pO1xuXHRcdH1cblx0fVxufTsiLCJfX3dlYnBhY2tfcmVxdWlyZV9fLm8gPSAob2JqLCBwcm9wKSA9PiAoT2JqZWN0LnByb3RvdHlwZS5oYXNPd25Qcm9wZXJ0eS5jYWxsKG9iaiwgcHJvcCkpIiwiLy8gZGVmaW5lIF9fZXNNb2R1bGUgb24gZXhwb3J0c1xuX193ZWJwYWNrX3JlcXVpcmVfXy5yID0gKGV4cG9ydHMpID0+IHtcblx0aWYodHlwZW9mIFN5bWJvbCAhPT0gJ3VuZGVmaW5lZCcgJiYgU3ltYm9sLnRvU3RyaW5nVGFnKSB7XG5cdFx0T2JqZWN0LmRlZmluZVByb3BlcnR5KGV4cG9ydHMsIFN5bWJvbC50b1N0cmluZ1RhZywgeyB2YWx1ZTogJ01vZHVsZScgfSk7XG5cdH1cblx0T2JqZWN0LmRlZmluZVByb3BlcnR5KGV4cG9ydHMsICdfX2VzTW9kdWxlJywgeyB2YWx1ZTogdHJ1ZSB9KTtcbn07IiwiLy8gbm8gYmFzZVVSSVxuXG4vLyBvYmplY3QgdG8gc3RvcmUgbG9hZGVkIGFuZCBsb2FkaW5nIGNodW5rc1xuLy8gdW5kZWZpbmVkID0gY2h1bmsgbm90IGxvYWRlZCwgbnVsbCA9IGNodW5rIHByZWxvYWRlZC9wcmVmZXRjaGVkXG4vLyBbcmVzb2x2ZSwgcmVqZWN0LCBQcm9taXNlXSA9IGNodW5rIGxvYWRpbmcsIDAgPSBjaHVuayBsb2FkZWRcbnZhciBpbnN0YWxsZWRDaHVua3MgPSB7XG5cdFwicGxhbnRfZWRpdFwiOiAwXG59O1xuXG4vLyBubyBjaHVuayBvbiBkZW1hbmQgbG9hZGluZ1xuXG4vLyBubyBwcmVmZXRjaGluZ1xuXG4vLyBubyBwcmVsb2FkZWRcblxuLy8gbm8gSE1SXG5cbi8vIG5vIEhNUiBtYW5pZmVzdFxuXG5fX3dlYnBhY2tfcmVxdWlyZV9fLk8uaiA9IChjaHVua0lkKSA9PiAoaW5zdGFsbGVkQ2h1bmtzW2NodW5rSWRdID09PSAwKTtcblxuLy8gaW5zdGFsbCBhIEpTT05QIGNhbGxiYWNrIGZvciBjaHVuayBsb2FkaW5nXG52YXIgd2VicGFja0pzb25wQ2FsbGJhY2sgPSAocGFyZW50Q2h1bmtMb2FkaW5nRnVuY3Rpb24sIGRhdGEpID0+IHtcblx0dmFyIFtjaHVua0lkcywgbW9yZU1vZHVsZXMsIHJ1bnRpbWVdID0gZGF0YTtcblx0Ly8gYWRkIFwibW9yZU1vZHVsZXNcIiB0byB0aGUgbW9kdWxlcyBvYmplY3QsXG5cdC8vIHRoZW4gZmxhZyBhbGwgXCJjaHVua0lkc1wiIGFzIGxvYWRlZCBhbmQgZmlyZSBjYWxsYmFja1xuXHR2YXIgbW9kdWxlSWQsIGNodW5rSWQsIGkgPSAwO1xuXHRpZihjaHVua0lkcy5zb21lKChpZCkgPT4gKGluc3RhbGxlZENodW5rc1tpZF0gIT09IDApKSkge1xuXHRcdGZvcihtb2R1bGVJZCBpbiBtb3JlTW9kdWxlcykge1xuXHRcdFx0aWYoX193ZWJwYWNrX3JlcXVpcmVfXy5vKG1vcmVNb2R1bGVzLCBtb2R1bGVJZCkpIHtcblx0XHRcdFx0X193ZWJwYWNrX3JlcXVpcmVfXy5tW21vZHVsZUlkXSA9IG1vcmVNb2R1bGVzW21vZHVsZUlkXTtcblx0XHRcdH1cblx0XHR9XG5cdFx0aWYocnVudGltZSkgdmFyIHJlc3VsdCA9IHJ1bnRpbWUoX193ZWJwYWNrX3JlcXVpcmVfXyk7XG5cdH1cblx0aWYocGFyZW50Q2h1bmtMb2FkaW5nRnVuY3Rpb24pIHBhcmVudENodW5rTG9hZGluZ0Z1bmN0aW9uKGRhdGEpO1xuXHRmb3IoO2kgPCBjaHVua0lkcy5sZW5ndGg7IGkrKykge1xuXHRcdGNodW5rSWQgPSBjaHVua0lkc1tpXTtcblx0XHRpZihfX3dlYnBhY2tfcmVxdWlyZV9fLm8oaW5zdGFsbGVkQ2h1bmtzLCBjaHVua0lkKSAmJiBpbnN0YWxsZWRDaHVua3NbY2h1bmtJZF0pIHtcblx0XHRcdGluc3RhbGxlZENodW5rc1tjaHVua0lkXVswXSgpO1xuXHRcdH1cblx0XHRpbnN0YWxsZWRDaHVua3NbY2h1bmtJZF0gPSAwO1xuXHR9XG5cdHJldHVybiBfX3dlYnBhY2tfcmVxdWlyZV9fLk8ocmVzdWx0KTtcbn1cblxudmFyIGNodW5rTG9hZGluZ0dsb2JhbCA9IGdsb2JhbFRoaXNbXCJ3ZWJwYWNrQ2h1bmtcIl0gPSBnbG9iYWxUaGlzW1wid2VicGFja0NodW5rXCJdIHx8IFtdO1xuY2h1bmtMb2FkaW5nR2xvYmFsLmZvckVhY2god2VicGFja0pzb25wQ2FsbGJhY2suYmluZChudWxsLCAwKSk7XG5jaHVua0xvYWRpbmdHbG9iYWwucHVzaCA9IHdlYnBhY2tKc29ucENhbGxiYWNrLmJpbmQobnVsbCwgY2h1bmtMb2FkaW5nR2xvYmFsLnB1c2guYmluZChjaHVua0xvYWRpbmdHbG9iYWwpKTsiLCIiLCIvLyBzdGFydHVwXG4vLyBMb2FkIGVudHJ5IG1vZHVsZSBhbmQgcmV0dXJuIGV4cG9ydHNcbi8vIFRoaXMgZW50cnkgbW9kdWxlIGRlcGVuZHMgb24gb3RoZXIgbG9hZGVkIGNodW5rcyBhbmQgZXhlY3V0aW9uIG5lZWQgdG8gYmUgZGVsYXllZFxudmFyIF9fd2VicGFja19leHBvcnRzX18gPSBfX3dlYnBhY2tfcmVxdWlyZV9fLk8odW5kZWZpbmVkLCBbXCJ2ZW5kb3JzLW5vZGVfbW9kdWxlc19qcXVlcnlfZGlzdF9qcXVlcnlfanNcIl0sICgpID0+IChfX3dlYnBhY2tfcmVxdWlyZV9fKFwiLi9hc3NldHMvanMvcGxhbnRfZWRpdC5qc1wiKSkpXG5fX3dlYnBhY2tfZXhwb3J0c19fID0gX193ZWJwYWNrX3JlcXVpcmVfXy5PKF9fd2VicGFja19leHBvcnRzX18pO1xuIiwiIl0sIm5hbWVzIjpbIiQiLCIkd3JhcHBlcl9zZW5zb3JzIiwib24iLCJlIiwicHJldmVudERlZmF1bHQiLCJjbG9zZXN0IiwicmVtb3ZlIiwicHJvdG90eXBlIiwiZGF0YSIsImluZGV4IiwibmV3Rm9ybSIsInJlcGxhY2UiLCJhcHBlbmQiLCJGb3VuZGF0aW9uIiwicmVJbml0IiwiJHdyYXBwZXJfbW9kdWxlIiwiJHdyYXBwZXJfc3Vuc2hhZGluZyIsIlN3YWwiLCJmaXJlIiwidGl0bGUiLCJ0ZXh0IiwiaWNvbiIsInNob3dDYW5jZWxCdXR0b24iLCJjb25maXJtQnV0dG9uQ29sb3IiLCJ0aW1lciIsImNvbmZpcm1CdXR0b25UZXh0IiwiY2FuY2VsQnV0dG9uVGV4dCIsInNob3dDbG9zZUJ1dHRvbiIsImFsbG93T3V0c2lkZUNsaWNrIiwiYWxsb3dFc2NhcGVLZXkiLCJmb2N1c0NvbmZpcm0iLCJ0aGVuIiwicmVzdWx0IiwiaXNDb25maXJtZWQiLCJjbGljayIsImluZGV4cm93IiwiY3BmaWVsZDAiLCJjcGZpZWxkMSIsImNwZmllbGQyIiwiY3BmaWVsZDMiLCJjcGZpZWxkNCIsImNwZmllbGQ1IiwiY3BmaWVsZDYiLCJjcGZpZWxkNyIsImNwZmllbGQ4IiwiY3BmaWVsZDkiLCJjcGZpZWxkMTAiLCJjcGZpZWxkMTEiLCJjcGZpZWxkMTIiLCJjcGZpZWxkMTMiLCJjcGZpZWxkMTQiLCJud2ZpZWxkMCIsIm53ZmllbGQxIiwibndmaWVsZDIiLCJud2ZpZWxkMyIsIm53ZmllbGQ0IiwibndmaWVsZDUiLCJud2ZpZWxkNiIsIm53ZmllbGQ3IiwibndmaWVsZDgiLCJud2ZpZWxkOSIsIm53ZmllbGQxMCIsIm53ZmllbGQxMSIsIm53ZmllbGQxMiIsIm53ZmllbGQxMyIsIm53ZmllbGQxNCIsInZhbCIsIiR3cmFwcGVyX3RpbWVjb25maWciLCIkd3JhcHBlcl9ldmVudG1haWwiLCIkd3JhcHBlcl9sZWdlbmQiLCIkd3JhcHBlcl9lcGMiLCIkd3JhcHBlcl9wdnN5c3QiLCIkd3JhcHBlcl95aWVsZCIsIiR3cmFwcGVyX2Vjb25vbWljIiwiJHdyYXBwZXJfZ3JvdXAiLCIkd3JhcHBlcl91c2VfbW9kdWxlIiwiZ3JvdXBJZCIsImN1cnJlbnRUYXJnZXQiLCJkYXRhc2V0IiwiZ3JvdXBpZCIsIiR3cmFwcGVyX21vbnRoIiwiJHdyYXBwZXJfcGxhbnRfbW9udGgiLCIkd3JhcHBlcl9hY2dyb3VwIl0sInNvdXJjZVJvb3QiOiIifQ==