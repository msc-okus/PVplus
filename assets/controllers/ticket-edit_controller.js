import { Controller } from '@hotwired/stimulus';
import { useDispatch } from 'stimulus-use';
import { Reveal } from 'foundation-sites';
import $ from 'jquery';
import * as Stimulus from "@hotwired/stimulus";


export default class extends Controller {
    static targets = ['modal', 'modalBody', 'splitModal'];
    static values = {
        formUrl: String,
        splitUrl: String,
    }
    modal = null;
    splitModal = null;

    connect() {
        useDispatch(this);
    }

    async openModal(event) {
        this.modalBodyTarget.innerHTML = 'Loading ...';
        this.modal = new Reveal($(this.modalTarget));
        this.modal.open();
        this.modalBodyTarget.innerHTML = await $.ajax(this.formUrlValue);
    }

    closeTicket(event) {
        event.preventDefault();
        this.modal.destroy();
    }

    async saveTicket(event) {
        event.preventDefault();
        const  $form = $(this.modalBodyTarget).find('form');
        try {
            await $.ajax({
                url: this.formUrlValue,
                method: $form.prop('method'),
                data: $form.serialize(),
            });
            this.dispatch('success');
            this.modal.destroy();
        } catch(e) {
            this.modalBodyTarget.innerHTML = e.responseText;
        }
    }


    openSplitTicket(event){

        console.log(this.data);
        console.log(this.testValue);
        event.preventDefault();
        this.splitModal = new Reveal($(this.splitModalTarget));
        this.splitModal.open();
    }

    closeSplitTicket(event){
        event.preventDefault();
        this.splitModal.destroy();
    }
    getId(event){
        return "hola";
    }
    async splitTicket(event) {
        event.preventDefault();
        const  $form = $(this.modalBodyTarget).find('.js-split-ticket');
        try {
            const response = await $.ajax({
                url: this.splitUrlValue,
                data: $form.serialize(),
            });
            this.modalBodyTarget.innerHTML = response ;
            this.splitModal.destroy();
        } catch(e) {
            console.log(e);

            this.modalBodyTarget.innerHTML = e.responseText;
        }

    }

    async dataGap({params: {id}}){
    }
}