import { Controller } from '@hotwired/stimulus';
import { useDispatch } from 'stimulus-use';
import { Reveal } from 'foundation-sites';
import $ from 'jquery';



export default class extends Controller {
    static targets = ['modal', 'modalBody', 'splitModal', 'splitForm'];
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

        event.preventDefault();
        this.splitModal = new Reveal($(this.splitModalTarget));
        this.splitModal.open();
    }

    closeSplitTicket(event){
        event.preventDefault();
        this.splitModal.destroy();
    }
    getId(event){
    }
    async splitTicket({params: {id}}) {;
        console.log("/ticket/split/"+id);
        try {
            const response = await $.ajax({
                url: "/ticket/split/"+id,
                //data: $form.serialize(),
                data: $(this.splitFormTarget).find('.js-split-ticket').serialize()
            });

            this.splitModal.destroy();
            this.modalBodyTarget.innerHTML = response ;

        } catch(e) {
            console.log(e);
            this.modalBodyTarget.innerHTML = e.responseText;
        }

    }

    async dataGap({params: {id}}){
    }
}