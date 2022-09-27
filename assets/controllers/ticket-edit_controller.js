import { Controller } from '@hotwired/stimulus';
import { useDispatch } from 'stimulus-use';
import { Reveal } from 'foundation-sites';
import $ from 'jquery';

export default class extends Controller {
    static targets = ['modal', 'modalBody', 'splitModal', 'splitForm', 'switch', 'deactivable', 'anlage', 'saveButton'];
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

        if (this.formUrlValue === '/ticket/create') {
            this.modalBodyTarget.innerHTML = await $.ajax({
                url: this.formUrlValue,
                data: {'anlage': $(this.anlageTarget).val()},
            });
                $(this.saveButtonTarget).attr('disabled', 'disabled');
        }
        else{
            this.modalBodyTarget.innerHTML = await $.ajax({
                url: this.formUrlValue,
            });
        }
        $(this.modalBodyTarget).foundation();

    }

    setBody(html){
        this.modalBodyTarget.innerHTML = html;
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

    async reload(event){
        this.modalBodyTarget.innerHTML = await $.ajax(this.formUrlValue);
    }

    check(){
        let inverterString = "";
        if ($(this.switchTarget).prop('checked')) {
            $(this.modalBodyTarget).find('input:checkbox[class=js-checkbox]').each(function () {
                $(this).prop('checked', true);
                if (inverterString == '') inverterString = inverterString + $(this).prop('name');
                else inverterString = inverterString + ', ' + $(this).prop('name');
            });

            $(this.modalBodyTarget).find('#ticket_form_inverter').val(inverterString);
        } else {
            $(this.modalBodyTarget).find('input:checkbox[class=js-checkbox]').each(function(){
                $(this).prop('checked', false);
                $(this.modalBodyTarget).find('#ticket_form_inverter').val('');
            });
        }
        console.log(inverterString);

        if (inverterString == '') {
            $(this.saveButtonTarget).attr('disabled', 'disabled');
        }
        else {
            $(this.saveButtonTarget).removeAttr('disabled');
        }
    }

    checkInverter(){

        let inverterString = '';
        /*$('input:checkbox[class=js-checkbox]:checked')*/
        $(this.modalBodyTarget).find('input:checkbox[class=js-checkbox]:checked').each(function (){
            if (inverterString == '') inverterString = inverterString + $(this).prop('name');
            else inverterString = inverterString + ', ' + $(this).prop('name');
        });
        console.log(inverterString);
        if (inverterString == '') {
            $(this.saveButtonTarget).attr('disabled', 'disabled');
        }
        else {
            $(this.saveButtonTarget).removeAttr('disabled');
        }
        $(this.modalBodyTarget).find('#ticket_form_inverter').val(inverterString);

    }

    toggle(){
        const $button = $(this.deactivableTargets);
        if ($button.attr('disabled')) {
            $button.removeAttr('disabled');
        }
    }


}