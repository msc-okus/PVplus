import { Controller } from '@hotwired/stimulus';
import { useDispatch } from 'stimulus-use';
import { Reveal } from 'foundation-sites';
import $ from 'jquery';

export default class extends Controller {
    static targets = ['splitAlert', 'modal', 'modalBody', 'splitModal', 'splitForm', 'switch', 'deactivable', 'anlage', 'saveButton', 'AlertFormat', 'AlertDates', 'formBegin', 'formEnd', 'splitButton','splitDeploy'];
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
        this.dispatch('success');
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
        $(document).foundation();
    }

    checkSelect({ params: { edited }}){
        let body = $(this.modalBodyTarget);

        body.find('.js-div-split-a').each(function(){
            $(this).addClass('is-hidden');
            $(this).find('.js-checkbox-split-a').prop('checked', false);
        });
        body.find('.js-div-split-b').each(function(){
            $(this).addClass('is-hidden');
            $(this).find('.js-checkbox-split-b').prop('checked', false);
        });
        let inverterString = '';

        if ($(this.switchTarget).prop('checked')) {
            body.find('input:checkbox[class=js-checkbox]').each(function () {
                $(this).prop('checked', true);
                if (inverterString == '') {inverterString = inverterString + $(this).prop('name');}
                else {inverterString = inverterString + ', ' + $(this).prop('name');}
                body.find($('#div-split-'+$(this).prop('name')+'a')).removeClass('is-hidden');
                body.find($('#split-'+$(this).prop('name')+'a')).prop('checked', true);
                body.find($('#div-split-'+$(this).prop('name')+'b')).removeClass('is-hidden');
            });
            if (edited == true) {
                $(this.splitDeployTarget).removeAttr('disabled');
            }
        } else {
            $(this.modalBodyTarget).find('input:checkbox[class=js-checkbox]').each(function(){
                $(this).prop('checked', false);
            });
            $(this.splitDeployTarget).attr('disabled', 'disabled');

        }
        $(this.modalBodyTarget).find('#ticket_form_inverter').val(inverterString);


        if (inverterString == '') {
            $(this.saveButtonTarget).attr('disabled', 'disabled');
        }
        else {
            $(this.saveButtonTarget).removeAttr('disabled');
        }
    }

    checkInverter({ params: { edited }}){

        let inverterString = '';
        let body = $(this.modalBodyTarget);
        let counter = 0;
        body.find('.js-div-split-a').each(function(){
            $(this).addClass('is-hidden');
            $(this).find('.js-checkbox-split-a').prop('checked', false);
        });
        body.find('.js-div-split-b').each(function(){
            $(this).addClass('is-hidden');
            $(this).find('.js-checkbox-split-b').prop('checked', false);
        });
        body.find('input:checkbox[class=js-checkbox]:checked').each(function (){
            counter ++;
            if (inverterString == '') {inverterString = inverterString + $(this).prop('name');}
            else {inverterString = inverterString + ', ' + $(this).prop('name');}
            body.find($('#div-split-'+$(this).prop('name')+'a')).removeClass('is-hidden');
            body.find($('#div-split-'+$(this).prop('name')+'b')).removeClass('is-hidden');
            body.find($('#split-'+$(this).prop('name')+'a')).prop('checked', true);

        });

        if (counter <= 1 ) {
            $(this.splitDeployTarget).attr('disabled', 'disabled');
        }
        else {
            if (edited == true) {
                $(this.splitDeployTarget).removeAttr('disabled');
            }
        }
        if (inverterString == '') {
            $(this.saveButtonTarget).attr('disabled', 'disabled');
        }
        else {
            $(this.saveButtonTarget).removeAttr('disabled');
        }
        $(this.modalBodyTarget).find('#ticket_form_inverter').val(inverterString);
    }

    checkDates() { // What do you check ????
        const valueBegin = $(this.formBeginTarget).prop('value');
        const valueEnd = $(this.formEndTarget).prop('value');


        const date1 = new Date(valueBegin);
        const date2 = new Date(valueEnd);
        date1.setSeconds(0);
        date2.setSeconds(0);
        const timestamp1 = date1.getTime();
        const timestamp2 = date2.getTime();


        if (timestamp2 >= timestamp1){
            $(this.AlertDatesTarget).addClass('is-hidden');
            $(this.saveButtonTarget).removeAttr('disabled');
        } else {
            $(this.AlertDatesTarget).removeClass('is-hidden');
            $(this.saveButtonTarget).attr('disabled', 'disabled');
        }

        if ((timestamp1 % 900000 == 0) && (timestamp2 % 900000 == 0)){
            $(this.AlertFormatTarget).addClass('is-hidden');
            $(this.saveButtonTarget).removeAttr('disabled');
        } else {
            $(this.AlertFormatTarget).removeClass('is-hidden');
            $(this.saveButtonTarget).attr('disabled', 'disabled');
        }
    }

    toggle(){
        let $button = $(this.deactivableTargets);
        if ($button.attr('disabled')) {
            $button.removeAttr('disabled');
        }
    }

    checkInverterSplit1({ params: { id }}){
        let body = $(this.modalBodyTarget);
        let inverterStringa = '';
        let inverterStringb = '';
        if (body.find($('#split-'+id+'a')).prop('checked'))
        {
            body.find($('#split-'+id+'b')).prop('checked', false);
        }
        else
        {
            body.find($('#split-'+id+'b')).prop('checked', true);
        }

        $(this.modalBodyTarget).find('input:checkbox[class=js-checkbox-split-a]:checked').each(function (){
            if (inverterStringa == '') {inverterStringa = inverterStringa + $(this).prop('name');}
            else {inverterStringa = inverterStringa + ', ' + $(this).prop('name');}
        });

        $(this.modalBodyTarget).find('input:checkbox[class=js-checkbox-split-b]:checked').each(function (){
            if (inverterStringb == '') {inverterStringb = inverterStringb + $(this).prop('name');}
            else {inverterStringb = inverterStringb + ', ' + $(this).prop('name');}
        });
        if (inverterStringa == '' || inverterStringb == ''){
            $(this.splitButtonTarget).attr('disabled', 'disabled');
            $(this.splitAlertTarget).removeClass('is-hidden');
        }
        else{
            $(this.splitButtonTarget).removeAttr('disabled');
            $(this.splitAlertTarget).addClass('is-hidden');
        }
    }
    checkInverterSplit2({ params: { id }}){
        let body = $(this.modalBodyTarget);
        let inverterStringa = '';
        let inverterStringb = '';
        if (body.find($('#split-'+id+'b')).prop('checked'))
        {
            body.find($('#split-'+id+'a')).prop('checked', false);
        }
        else
        {
            body.find($('#split-'+id+'a')).prop('checked', true);
        }

        $(this.modalBodyTarget).find('input:checkbox[class=js-checkbox-split-a]:checked').each(function (){
            if (inverterStringa == '') {inverterStringa = inverterStringa + $(this).prop('name');}
            else {inverterStringa = inverterStringa + ', ' + $(this).prop('name');}
        });

        $(this.modalBodyTarget).find('input:checkbox[class=js-checkbox-split-b]:checked').each(function (){
            if (inverterStringb == '') {inverterStringb = inverterStringb + $(this).prop('name');}
            else {inverterStringb = inverterStringb + ', ' + $(this).prop('name');}
        });
        if (inverterStringa == '' || inverterStringb == ''){
            $(this.splitButtonTarget).attr('disabled', 'disabled');
            $(this.splitAlertTarget).removeClass('is-hidden');

        }
        else{
            $(this.splitButtonTarget).removeAttr('disabled');
            $(this.splitAlertTarget).addClass('is-hidden');
        }
        console.log(inverterStringa, inverterStringb);
    }

    async splitTicketByInverter({ params: { ticketid }}){
        let inverterStringa = '';
        let inverterStringb = '';

        $(this.modalBodyTarget).find('input:checkbox[class=js-checkbox-split-a]:checked').each(function (){
            if (inverterStringa == '') {inverterStringa = inverterStringa + $(this).prop('name');}
            else {inverterStringa = inverterStringa + ', ' + $(this).prop('name');}
        });

        $(this.modalBodyTarget).find('input:checkbox[class=js-checkbox-split-b]:checked').each(function (){
            if (inverterStringb == '') {inverterStringb = inverterStringb + $(this).prop('name');}
            else {inverterStringb = inverterStringb + ', ' + $(this).prop('name');}
        });

        try {
            event.preventDefault();
            const  $form = $(this.modalBodyTarget).find('form');
            try {
                await $.ajax({
                    url: this.formUrlValue,
                    method: $form.prop('method'),
                    data: $form.serialize(),
                });
            } catch(e) {
                this.modalBodyTarget.innerHTML = e.responseText;
            }
            this.modalBodyTarget.innerHTML = await $.ajax({
                url: '/ticket/splitbyinverter',
                data: {'id': ticketid,
                       'invertera' : inverterStringa,
                       'inverterb' : inverterStringb
                }
            });
            $(this.modalBodyTarget).foundation();

        } catch (e) {
             console.log('error');
        }
    }

}