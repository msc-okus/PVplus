import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';
import {Reveal} from "foundation-sites";
import Swal from 'sweetalert2';

export default class extends Controller {
    static targets = ['form'];
    static values = {

    }

    connect() {
        this.checkStatus()
    }

    checkStatus(){
        const $previewButton = $(this.formTarget).find('#import_pv_syst_form_preview');
        const $plantSelect = $(this.formTarget).find('#import_pv_syst_form_anlage');
        const $fileSelectButton = $(this.formTarget).find('#import_pv_syst_form_file');

        if (($plantSelect.val() > 0) && ($fileSelectButton.val() !== '')) {
            $previewButton.prop('disabled', false);
        } else {
            $previewButton.prop('disabled', true);
        }

    }


}
