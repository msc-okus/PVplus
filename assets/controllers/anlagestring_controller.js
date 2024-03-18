import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';

export default class extends Controller {
    static targets = ['list', 'searchForm','uploadForm','modal'];
    static values = {
        urlSearch: String,
    }

    connect() {
        this.uploadFormTarget.addEventListener('submit', this.handleFormSubmit.bind(this));
    }

    async search(event){
        event.preventDefault();
        const $searchReportform = $(this.searchFormTarget).find('form');
        this.listTarget.innerHTML = await $.ajax({
            url: this.urlSearchValue,
            method: $searchReportform.prop('method'),
            data: $searchReportform.serialize(),
        });
        $(document).foundation();
    }

    async handleFormSubmit(event) {
        this.modalTarget.style.display='block';
        }



}
