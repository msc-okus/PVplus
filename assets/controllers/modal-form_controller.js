import { Controller } from '@hotwired/stimulus';
import { useDispatch } from 'stimulus-use';
import { Reveal } from 'foundation-sites';
import $ from 'jquery';


export default class extends Controller {
    static targets = ['modalPdf','modal', 'modalBodyPdf', 'modalBody'];
    static values = {
        formUrl: String,
        submitUrl: String
    }

    modal = null;

    connect() {
        useDispatch(this);
    }

    async openModal(event) {
        this.modalBodyTarget.innerHTML = 'Loading ...';

        this.modal = new Reveal($(this.modalTarget));
        this.modal.open();

        this.modalBodyTarget.innerHTML = await $.ajax(this.formUrlValue);
    }

    async openModalCreate(event){
        this.modalBodyPdfTarget.innerHTML = 'Loading ...';
        this.modal = new Reveal($(this.modalPdfTarget));
        this.modal.open();

            this.modalBodyPdfTarget.innerHTML = await $.ajax({
                url: this.submitUrlValue,
                data: {'anlage': $(this.anlageTarget).val()},
            });
            $(this.saveButtonTarget).attr('disabled', 'disabled');

        $(this.modalBodyPdfTarget).foundation();

    }
    async createReport(event){
        event.preventDefault();
        const  $form = $(this.modalBodyPdfTarget).find('form');

        //console.log($form.serialize());
        console.log("here?");
        /*
        await $.ajax({
             url: this.submitUrlValue,
             data: $form.serialize(),
             method: 'get',
         });
         */
        //this.dispatch('success');
        this.modal.destroy();


    }

    async closeModal(){
        event.preventDefault();
        this.dispatch('success');
        this.modal.destroy();
    }

    async submitForm(event) {
        event.preventDefault();
        const  $form = $(this.modalBodyTarget).find('form');
        console.log($form);
        try {
            await $.ajax({
                url: this.formUrlValue,
                data: $form.serialize(),
                method: 'POST'
            });
            this.dispatch('success');
            this.modal.destroy();
        } catch (e) {
            this.modalBodyTarget.innerHTML = e.responseText;
        }
    }
}
