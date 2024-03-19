import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';

export default class extends Controller {
    static targets = ['list', 'searchForm','uploadForm','modal','popup','createForm'];
    static values = {
        urlSearch: String,
        urlDelete: String
    }

    connect() {
        this.uploadFormTarget.addEventListener('submit', this.handleUploadFormSubmit.bind(this));


    }
    disconnect() {
        this.popupTarget.style.display='none';
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

    async delete(event){
        event.preventDefault();
        const btn=event.currentTarget;
        const reportId=btn.dataset.anlagestringReportid;
        const filename=btn.dataset.anlagestringFilename;

        const $searchReportform = $(this.searchFormTarget).find('form');
        const anlIdInput = $('<input>').attr({
            type: 'hidden',
            name: 'reportId',
            value: reportId
        });

        const filenameInput = $('<input>').attr({
            type: 'hidden',
            name: 'filename',
            value: filename
        });

        $searchReportform.append(anlIdInput, filenameInput);

        this.listTarget.innerHTML = await $.ajax({
            url: this.urlDeleteValue,
            method: $searchReportform.prop('method'),
            data: $searchReportform.serialize(),
        });
        $(document).foundation();
    }



    async handleUploadFormSubmit(event) {
        this.modalTarget.style.display='block';
        }




}
