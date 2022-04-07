import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';

export default class extends Controller {
    static targets = ['list', 'reportForm', 'searchForm', 'createForm', 'required', 'deactivable'];
    static values = {
        urlCreate: String,
        urlSearch: String
    }

    connect() {

        //console.log($button);

    }

    toggle(){
        const $button = $(this.deactivableTargets);
        //console.log($button.attr('disabled'))
        if ($button.attr('disabled')) {
            $button.removeAttr('disabled')
        } else {
            $button.attr('disabled', 'disabled')
        }
    }

    handleInput() {
        const isRequiredFilled = this.requiredTargets.every(el => el.value !== '');
        //console.log(isRequiredFilled);
        if (isRequiredFilled) {
            this.toggle();
        }
    }

    async search(event){
        event.preventDefault();
        const $searchReportform = $(this.reportFormTarget).find('form');
        //console.log('hit search', $searchReportform);
        //console.log($searchReportform.serialize());
        this.listTarget.innerHTML = await $.ajax({
            url: this.urlSearchValue,
            method: $searchReportform.prop('method'),
            data: $searchReportform.serialize(),
        });

    }

    async create(event) {
        event.preventDefault();
        const $createReportform = $(this.reportFormTarget).find('form');
        //console.log('hit create');
        this.listTarget.innerHTML = await $.ajax({
            url: this.urlCreateValue,
            method: $createReportform.prop('method'),
            data: $createReportform.serialize(),
        });
    }
}