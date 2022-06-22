import { Controller } from '@hotwired/stimulus';
import { Reveal } from 'foundation-sites';
import $ from 'jquery';
// Import and register all TailwindCSS Components
//import { Modal } from "tailwindcss-stimulus-components"
//Controller.register('modal', Modal)

export default class extends Controller {
    static targets = ['modal', 'modalBody'];
    static values = {
        formUrl: String,
        reportId: String,
    }

    connect() {
        //console.log(this.reportIdValue);
    }

    async openModal(event) {
        console.log($(this.modalTarget).find('reveal'));
        const modal = new Reveal($(this.modalTarget).find('#modal'.reportIdValue));
        modal.open();
        console.log(modal);
        modal.destroy();
        this.modalBodyTarget.innerHTML = 'Loading ...';
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
            console.log('success!');
        } catch (e) {
            this.modalBodyTarget.innerHTML = e.responseText;
        }
    }
}
