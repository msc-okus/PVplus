/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)


// start the Stimulus application
import '../bootstrap';

import '../styles/app.scss';

import 'foundation-sites';
import 'foundation-datepicker';


import $ from 'jquery';
//global.$ = $;

$(document).foundation();

$('.my-alert-box').closest('[data-alert]').fadeOut(8000);
