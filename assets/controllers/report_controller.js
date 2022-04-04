import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';

export default class extends Controller {
    static targets = ['list', 'reportForm', 'searchForm', 'createForm'];
    static values = {
        urlCreate: String,
        urlSearch: String
    }

    connect() {}


    async search(event){
        event.preventDefault();
        const $searchReportform = $(this.reportFormTarget).find('form');
        console.log('hit search', $searchReportform);
        console.log($searchReportform.serialize());
        this.listTarget.innerHTML = await $.ajax({
            url: this.urlSearchValue,
            method: $searchReportform.prop('method'),
            data: $searchReportform.serialize(),
        });

    }

    async create(event) {
        event.preventDefault();
        const $createReportform = $(this.reportFormTarget).find('form');

        console.log('hit create');

        this.listTarget.innerHTML = await $.ajax({
            url: this.urlCreateValue,
            method: $createReportform.prop('method'),
            data: $createReportform.serialize(),
        });
    }
}
