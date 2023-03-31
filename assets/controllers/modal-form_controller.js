import { Controller } from '@hotwired/stimulus';
import { useDispatch } from 'stimulus-use';
import { Reveal } from 'foundation-sites';
import $ from 'jquery';


export default class extends Controller {
    static targets = ['modalPdf','modal', 'modalBodyPdf', 'modalBody', 'createButton'];
    static values = {
        formUrl: String,
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

    async openModalCreate({ params: { id }}){
        this.modalBodyPdfTarget.innerHTML = 'Loading ...';
        this.modal = new Reveal($(this.modalPdfTarget));
        this.modal.open();

            this.modalBodyPdfTarget.innerHTML = await $.ajax({
                url: '/reporting/pdf/'.concat(id),
                data: {'anlage': $(this.anlageTarget).val()},
            });
            $(this.saveButtonTarget).attr('disabled', 'disabled');

        $(this.modalBodyPdfTarget).foundation();

    }
    async createReport(){
        const  $form = $(this.modalBodyTarget).find('form');
        console.log($form);
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
