import { Controller } from '@hotwired/stimulus';
import { useDispatch } from 'stimulus-use';
import { Reveal } from 'foundation-sites';
import $ from 'jquery';


export default class extends Controller {
    static targets =    ['splitAlert', 'modal', 'modalBody', 'splitModal', 'splitForm', 'switch', 'deactivable',
        'anlage', 'saveButton'];
    static values = {
        formUrl: String,
    }
    modal = null;
    splitModal = null;
    contactModal = null;
    contactCreateModal = null;
    timelineModal = null;

    connect() {
        useDispatch(this);
    }
    async openModal(event) {

        this.modalBodyTarget.innerHTML = 'Loading ...';
        this.modal = new Reveal($(this.modalTarget));
        this.modal.open();
        if (this.formUrlValue == '/ticket/edit') {
            this.modalBodyTarget.innerHTML = await $.ajax({
                url: this.formUrlValue,
                data: {'anlage': $(this.anlageTarget).val()},
            });
            $(this.saveButtonTarget).attr('disabled', 'disabled');
        } else {
            this.modalBodyTarget.innerHTML = await $.ajax({
                url: this.formUrlValue,
            });
        }
        this.checkCategory();

        $(this.modalBodyTarget).foundation();
    }

    closeModal(event) {
        event.preventDefault();
        this.dispatch('success');
        this.modal.destroy();
    }

    toggle(){

        let $button = $(this.deactivableTarget);
        if ($button.attr('disabled')) {
            $button.removeAttr('disabled');
        }
    }


}