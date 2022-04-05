import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';

export default class extends Controller {
    static targets = ['list', 'reportForm', 'searchForm', 'createForm', 'required', 'deactivable'];
    static values = {
        urlCreate: String,
        urlSearch: String
    }

    connect() {
    }

    toggle(){
        const $button = $(this.deactivableTargets);

        if ($button.attr('disabled')) {
            $button.removeAttr('disabled')
        } else {
           $button.attr('disabled', 'disabled')
        }
    }

    handleInput() {
        const $button = $(this.deactivableTargets);
        const isRequiredFilled = this.requiredTargets.every(el => el.value);
        if (isRequiredFilled) {
            $button.removeAttr('disabled')
        } else {
            $button.attr('disabled', 'disabled')
        }
    }

    async search(event){
        event.preventDefault();
        const $searchReportform = $(this.reportFormTarget).find('form');
        this.listTarget.innerHTML = await $.ajax({
            url: this.urlSearchValue,
            method: $searchReportform.prop('method'),
            data: $searchReportform.serialize(),
        });
    }

    async create(event) {
        event.preventDefault();
        const $createReportform = $(this.reportFormTarget).find('form');
        this.listTarget.innerHTML = await $.ajax({
            url: this.urlCreateValue,
            method: $createReportform.prop('method'),
            data: $createReportform.serialize(),
        });
    }
}
