import { Controller } from '@hotwired/stimulus';
import { useDispatch } from 'stimulus-use';
import { Reveal } from 'foundation-sites';
import $ from 'jquery';

export default class extends Controller {
    static targets = ['splitAlert', 'modal', 'modalBody', 'splitModal', 'splitForm', 'switch', 'deactivable',
                        'anlage', 'saveButton', 'AlertFormat', 'AlertDates', 'formBegin', 'formEnd', 'splitButton',
                        'splitDeploy','AlertInverter', 'Callout', 'formCategory', 'AlertCategory'];
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
        } else {
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
            inverterString = '*';
        } else {
            $(this.modalBodyTarget).find('input:checkbox[class=js-checkbox]').each(function(){
                $(this).prop('checked', false);
            });
            $(this.splitDeployTarget).attr('disabled', 'disabled');

        }
        $(this.modalBodyTarget).find('#ticket_form_inverter').val(inverterString);


        if (inverterString == '') {
            $(this.CalloutTarget).removeClass('is-hidden');
            $(this.AlertInverterTarget).removeClass('is-hidden');
            $(this.saveButtonTarget).attr('disabled', 'disabled');
            if (timestamp2 > timestamp1){
                $(this.AlertDatesTarget).addClass('is-hidden');
                if ((timestamp1 % 900000 == 0) && (timestamp2 % 900000 == 0)){
                    $(this.AlertFormatTarget).addClass('is-hidden');
                    $(this.saveButtonTarget).removeAttr('disabled');
                } else {
                    $(this.AlertFormatTarget).removeClass('is-hidden');
                    $(this.saveButtonTarget).attr('disabled', 'disabled');
                }
            } else {
                $(this.AlertDatesTarget).removeClass('is-hidden');
                $(this.saveButtonTarget).attr('disabled', 'disabled');
                if ((timestamp1 % 900000 == 0) && (timestamp2 % 900000 == 0)){
                    $(this.AlertFormatTarget).addClass('is-hidden');
                    $(this.saveButtonTarget).removeAttr('disabled');
                } else {
                    $(this.AlertFormatTarget).removeClass('is-hidden');
                    $(this.saveButtonTarget).attr('disabled', 'disabled');
                }
            }
        }
        else {
            $(this.AlertInverterTarget).addClass('is-hidden');
            $(this.CalloutTarget).addClass('is-hidden');
            if (timestamp2 > timestamp1){
                $(this.AlertDatesTarget).addClass('is-hidden');
                if ((timestamp1 % 900000 == 0) && (timestamp2 % 900000 == 0)){
                    $(this.AlertFormatTarget).addClass('is-hidden');
                    $(this.saveButtonTarget).removeAttr('disabled');
                } else {
                    $(this.CalloutTarget).removeClass('is-hidden');
                    $(this.AlertFormatTarget).removeClass('is-hidden');
                    $(this.saveButtonTarget).attr('disabled', 'disabled');
                }
            } else {
                $(this.CalloutTarget).removeClass('is-hidden')
                $(this.AlertDatesTarget).removeClass('is-hidden');
                $(this.saveButtonTarget).attr('disabled', 'disabled');
                if ((timestamp1 % 900000 == 0) && (timestamp2 % 900000 == 0)){
                    $(this.AlertFormatTarget).addClass('is-hidden');
                    $(this.saveButtonTarget).removeAttr('disabled');
                } else {
                    $(this.CalloutTarget).removeClass('is-hidden');
                    $(this.AlertFormatTarget).removeClass('is-hidden');
                    $(this.saveButtonTarget).attr('disabled', 'disabled');
                }
            }
        }
    }

    saveCheck({ params: { edited }}){
        console.log('hey');
        //getting a string with the inverters so later we can check if there is any or none
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
        console.log(body.find('input:checkbox[class=js-checkbox]'));
        //here we get the values of the date forms to check if they are valid
        const valueBegin = $(this.formBeginTarget).prop('value');
        const valueEnd = $(this.formEndTarget).prop('value');


        const date1 = new Date(valueBegin);
        const date2 = new Date(valueEnd);
        date1.setSeconds(0);
        date2.setSeconds(0);
        const timestamp1 = date1.getTime();
        const timestamp2 = date2.getTime();

        const cat = $(this.formCategoryTarget).val();
        //allowing split check
        if (counter <= 1 ) {
            $(this.splitDeployTarget).attr('disabled', 'disabled');
        }
        else {
            if (edited == true) {
                $(this.splitDeployTarget).removeAttr('disabled');
            }
        }

        if (inverterString == '') {
            $(this.CalloutTarget).removeClass('is-hidden');
            $(this.AlertInverterTarget).removeClass('is-hidden');
            $(this.saveButtonTarget).attr('disabled', 'disabled');
            if (timestamp2 > timestamp1){
                $(this.AlertDatesTarget).addClass('is-hidden');
                if ((timestamp1 % 900000 == 0) && (timestamp2 % 900000 == 0)){
                    $(this.AlertFormatTarget).addClass('is-hidden');
                    if (cat == ""){
                        $(this.AlertCategoryTarget).removeClass('is-hidden');
                    }
                    else{
                        $(this.AlertCategoryTarget).addClass('is-hidden');
                    }
                } else {
                    $(this.AlertFormatTarget).removeClass('is-hidden');
                    if (cat == ""){
                        $(this.AlertCategoryTarget).removeClass('is-hidden');
                    }
                    else{
                        $(this.AlertCategoryTarget).addClass('is-hidden');
                    }
                }
            } else {
                $(this.AlertDatesTarget).removeClass('is-hidden');
                if ((timestamp1 % 900000 == 0) && (timestamp2 % 900000 == 0)){
                    $(this.AlertFormatTarget).addClass('is-hidden');
                    if (cat == ""){
                        $(this.AlertCategoryTarget).removeClass('is-hidden');
                    }
                    else{
                        $(this.AlertCategoryTarget).addClass('is-hidden');
                    }
                } else {
                    $(this.AlertFormatTarget).removeClass('is-hidden');
                    if (cat == ""){
                        $(this.AlertCategoryTarget).removeClass('is-hidden');
                    }
                    else{
                        $(this.AlertCategoryTarget).addClass('is-hidden');
                    }
                }
            }
        }
        else {
            $(this.AlertInverterTarget).addClass('is-hidden');
            $(this.CalloutTarget).addClass('is-hidden');
            $(this.saveButtonTarget).removeAttr('disabled');
            if (timestamp2 > timestamp1){
                $(this.AlertDatesTarget).addClass('is-hidden');
                if ((timestamp1 % 900000 == 0) && (timestamp2 % 900000 == 0)){
                    $(this.AlertFormatTarget).addClass('is-hidden');
                    if (cat == ""){
                        $(this.CalloutTarget).removeClass('is-hidden');
                        $(this.AlertCategoryTarget).removeClass('is-hidden');
                        $(this.saveButtonTarget).attr('disabled', 'disabled');
                    }
                    else{
                        $(this.AlertCategoryTarget).addClass('is-hidden');
                    }
                } else {
                    $(this.CalloutTarget).removeClass('is-hidden');
                    $(this.AlertFormatTarget).removeClass('is-hidden');
                    $(this.saveButtonTarget).attr('disabled', 'disabled');
                    if (cat == ""){
                        $(this.AlertCategoryTarget).removeClass('is-hidden');
                    }
                    else{
                        $(this.AlertCategoryTarget).addClass('is-hidden');
                    }
                }
            } else {
                $(this.CalloutTarget).removeClass('is-hidden')
                $(this.AlertDatesTarget).removeClass('is-hidden');
                $(this.saveButtonTarget).attr('disabled', 'disabled');

                    if ((timestamp1 % 900000 == 0) && (timestamp2 % 900000 == 0)){
                        $(this.AlertFormatTarget).addClass('is-hidden');
                        if (cat == ""){
                            $(this.AlertCategoryTarget).removeClass('is-hidden');
                        }
                        else{
                            $(this.AlertCategoryTarget).addClass('is-hidden');
                        }
                    } else {
                        $(this.AlertFormatTarget).removeClass('is-hidden');
                        if (cat == ""){
                            $(this.AlertCategoryTarget).removeClass('is-hidden');
                        }
                        else{
                            $(this.AlertCategoryTarget).addClass('is-hidden');
                        }
                    }
            }
        }

        $(this.modalBodyTarget).find('#ticket_form_inverter').val(inverterString);
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