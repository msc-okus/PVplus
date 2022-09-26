import { Controller } from '@hotwired/stimulus';
import { useDispatch } from 'stimulus-use';
import { Reveal } from 'foundation-sites';
import $ from 'jquery';

export default class extends Controller {
    static targets = ['modal', 'modalBody', 'splitModal', 'splitForm', 'switch', 'deactivable', 'anlage'];
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
        //const  $form = $(this.modalBodyTarget).find('form');
        let string = "";
        if($(this.switchTarget).prop('checked')) {
            $('input:checkbox[class=js-checkbox]').each(function () {
                $(this).prop('checked', true);
                if (string === "") string = string + $(this).prop('name');
                else string = string + ", " + $(this).prop('name');
            });

            $('#ticket_form_inverter').val(string);
        }
        else {
            $('input:checkbox[class=js-checkbox]').each(function(){
                $(this).prop('checked', false);
                $('#ticket_form_inverter').val('');
            });
        }
    }

    checkInverter(){
        let string = "";
        $('input:checkbox[class=js-checkbox]:checked').each(function (){
            if (string == "") string = string + $(this).prop('name');
            else string = string + ", " + $(this).prop('name');
        });
        $('#ticket_form_inverter').val(string);
    }

    toggle(){
        const $button = $(this.deactivableTargets);
        if ($button.attr('disabled')) {
            $button.removeAttr('disabled');
        }

    }
}