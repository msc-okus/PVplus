import {Controller} from '@hotwired/stimulus';
import $ from 'jquery';
import '../styles/new_dashboard.scss';
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
import 'foundation-sites';
import moment from "moment";

window.JSZip= JSZip;
pdfMake.vfs = pdfFonts.pdfMake.vfs;


export default class extends Controller {
    connect() {
        let t;


    }






}
