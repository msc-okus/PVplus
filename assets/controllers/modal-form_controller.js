import { Controller } from '@hotwired/stimulus';
import { useDispatch } from 'stimulus-use';
import { Reveal } from 'foundation-sites';
import $ from 'jquery';


export default class extends Controller {
    static targets = ['modal', 'modalBody'];
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

    async submitForm(event) {
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
        } catch (e) {
            this.modalBodyTarget.innerHTML = e.responseText;
        }
    }
}
