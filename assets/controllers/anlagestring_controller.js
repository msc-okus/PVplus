import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';
import Swal from 'sweetalert2'; // If using ES modules


export default class extends Controller {
    static targets = ['list', 'searchForm','uploadForm','modal','createForm'];
    static values = {
        urlSearch: String,
        urlDelete: String,
        urlGenerate: String,
        urlDownload: String,

    }

    connect() {
        this.uploadFormTarget.addEventListener('submit', this.handleUploadFormSubmit.bind(this));
        this.createFormTarget.addEventListener('submit', this.handleCreateFormSubmit.bind(this));


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

   async handleCreateFormSubmit(event){
       event.preventDefault();
       Swal.fire({
           title: 'Generate String Analysis Report',
           text: "The process will start in a few seconds in the background. You'll receive a notification once it's finished.",
           icon: 'info',
           timer: 2000,
           timerProgressBar: true,
           didOpen: () => {
               Swal.showLoading();
           },
       }).then((result) => {
           // After 3 seconds or when the user clicks the "OK" button, submit the form
           if (result.dismiss === Swal.DismissReason.timer || result.isConfirmed) {
               // Fetch the form element
               const form = this.createFormTarget;

               // Modify the form's action attribute if necessary
               form.action = this.urlGenerateValue;

               // Submit the form
               form.submit();
           }
       });
    }


}
