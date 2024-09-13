import { Controller } from '@hotwired/stimulus';
import { useDispatch } from 'stimulus-use';
import { Reveal } from 'foundation-sites';
import $ from 'jquery';


export default class extends Controller {
    static targets =    ['splitAlert', 'modal', 'modalBody', 'splitModal', 'splitForm', 'switch', 'deactivable',
                        'anlage', 'saveButton', 'AlertFormat', 'AlertDates', 'formBegin', 'formEnd', 'splitButton',
                        'splitDeploy','AlertInverter', 'Callout', 'formCategory', 'AlertCategory', 'headerExclude',
                        'headerReplace', 'headerReplacePower', 'headerReplaceIrr', 'headerHour', 'headerEnergyValue',
                        'headerIrrValue', 'headerCorrection', 'headerEvaluation', 'headerAktDep1', 'headerAktDep2',
                        'headerAktDep3', 'formReplace', 'fieldSensor', 'fieldReplacePower', 'fieldReplaceIrr', 'fieldHour',
                        'fieldEnergyValue', 'fieldIrrValue', 'fieldCorrection', 'fieldEvaluation', 'fieldAktDep1', 'fieldAktDep2',
                        'fieldAktDep3', 'formReplaceIrr', 'inverterDiv', 'formHour', 'formBeginHidden', 'formEndHidden', 'formBeginDate',
                        'formEndDate', 'formReasonSelect', 'formReasonText', 'headerReason', 'fieldReason', 'formkpiStatus', 'formReplaceG4N', 'headerFormKpi',
                        'headerPRMethod', 'fieldPRMethod', 'scope', 'reasonInput', 'sensorDiv', 'contactModal', 'modalContactBody', 'contactButton', 'modalContactCreateBody',
                        'contactModalCreate', 'modalTimelineBody', 'timelineModal', 'firstDateEnd', 'lastDateBegin','AlertInverterSubmit', 'attatchButton', 'fieldReplacePowerG4N', 'headerReplacePowerG4N'];
    static values = {
        formUrl: String,
        splitUrl: String,
        notifyUrl: String,
        createContactUrl: String,
        timelineUrl: String
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
        this.checkCategory();

        $(this.modalBodyTarget).foundation();
    }
    async openContactModal(event) {

        this.modalContactBodyTarget.innerHTML = 'Loading ...';
        this.contactModal = new Reveal($(this.contactModalTarget));
        this.contactModal.open();
        this.modalContactBodyTarget.innerHTML = await $.ajax({
            url: this.notifyUrlValue,
        });
        $(this.modalContactBodyTarget).foundation();
    }


    async openTimelineModal(event) {
        this.modalTimelineBodyTarget.innerHTML = 'Loading ...';
        this.timelineModal = new Reveal($(this.timelineModalTarget));
        this.timelineModal.open();
        this.modalTimelineBodyTarget.innerHTML = await $.ajax({
            url: this.timelineUrlValue,
        });
    }

    async openContactCreateModal(event){

        event.preventDefault();
        this.modalContactCreateBodyTarget.innerHTML = 'Loading ...';
        this.contactCreateModal = new Reveal($(this.contactModalCreateTarget));
        this.contactCreateModal.open();
        this.modalContactCreateBodyTarget.innerHTML = await $.ajax({
            url: this.createContactUrlValue,
        });
    }


    reasonCheck(){
        let reason = $(this.reasonInputTarget).val();
        $(this.formReasonSelectTarget).val(reason);
    }

    beginPlusTime(){
        let td = $(this.firstDateEndTarget);
        const valueBegin = $(this.formBeginTarget).prop('value');
        const valueEnd = td.find('input')[0].value;
        let date = new Date(valueBegin);
        let endDate = new Date(valueEnd);

        if ($(this.formHourTarget).prop('checked') == true){
            var addTime = 60;
        }
        else {
            var addTime = 15;
        }
        let newDate = new Date(date.getTime() + (addTime * 60000));
        if (newDate.getTime() < endDate.getTime()) {
            if (newDate.getMonth() < 9) {
                var Month = '0'.concat((newDate.getMonth() + 1).toString());
            } else {
                var Month = (newDate.getMonth() + 1).toString();
            }
            if (newDate.getDate() < 10) {
                var Day = '0'.concat(newDate.getDate().toString());
            } else {
                var Day = newDate.getDate().toString();
            }

            if (newDate.getHours() < 10) {
                var hour = '0'.concat(newDate.getHours().toString());
            } else {
                var hour = newDate.getHours().toString();
            }
            if (newDate.getMinutes() < 10) {
                var minutes = '0'.concat(newDate.getMinutes().toString());
            } else {
                var minutes = newDate.getMinutes().toString();
            }
            let newStringdate = newDate.getFullYear().toString().concat('-', Month, '-', Day, 'T', hour, ':', minutes);
            $(this.formBeginTarget).val(newStringdate);
            $(this.formBeginDateTarget).val(newStringdate);
            if ($(this.formHourTarget).prop('checked') == true) this.hourCheck();
        }
    }
    beginMinusTime(){

        if ($(this.formHourTarget).prop('checked') == true){
            var subTime = 60;
        }
        else {
            var subTime = 15;
        }
        const valueBegin = $(this.formBeginTarget).prop('value');
        let date = new Date(valueBegin);
        let newDate = new Date(date.getTime() - (subTime * 60000));

        if (newDate.getMonth() < 9) {
            var Month = '0'.concat((newDate.getMonth() + 1).toString());
        }
        else{
            var Month = (newDate.getMonth() + 1).toString();
        }
        if (newDate.getDate() < 10){
            var Day =  '0'.concat(newDate.getDate().toString());
        }
        else{
            var Day = newDate.getDate().toString();
        }

        if (newDate.getHours() < 10){
            var hour =  '0'.concat(newDate.getHours().toString());
        }else{
            var hour =  newDate.getHours().toString();
        }
        if (newDate.getMinutes() < 10){
            var minutes =  '0'.concat(newDate.getMinutes().toString());
            var minutes =  '0'.concat(newDate.getMinutes().toString());
        }else{
            var minutes =  newDate.getMinutes().toString();
        }

        let newStringdate = newDate.getFullYear().toString().concat('-', Month, '-',Day, 'T', hour, ':', minutes);
        $(this.formBeginTarget).val(newStringdate);
        $(this.formBeginDateTarget).val(newStringdate);
        if ($(this.formHourTarget).prop('checked') == true)this.hourCheck();


    }
    endPlusTime(){
        const valueEnd = $(this.formEndTarget).prop('value');
        let date = new Date(valueEnd);
        if ($(this.formHourTarget).prop('checked') == true){
            var addTime = 60;
        }
        else {
            var addTime = 15;
        }
        let newDate = new Date(date.getTime() + (addTime * 60000));


            if (newDate.getMonth() < 9) {
                var Month = '0'.concat((newDate.getMonth() + 1).toString());
            } else {
                var Month = (newDate.getMonth() + 1).toString();
            }
            if (newDate.getDate() < 10) {
                var Day = '0'.concat(newDate.getDate().toString());
            } else {
                var Day = newDate.getDate().toString();
            }
            if (newDate.getHours() < 10) {
                var hour = '0'.concat(newDate.getHours().toString());
            } else {
                var hour = newDate.getHours().toString();
            }
            if (newDate.getMinutes() < 10) {
                var minutes = '0'.concat(newDate.getMinutes().toString());
            } else {
                var minutes = newDate.getMinutes().toString();
            }

            let newStringdate = newDate.getFullYear().toString().concat('-', Month, '-', Day, 'T', hour, ':', minutes);
            $(this.formEndTarget).val(newStringdate);
            $(this.formEndDateTarget).val(newStringdate);

    }
    endMinusTime(){
        let td = $(this.lastDateBeginTarget);
        const valueBegin = td.find('input')[0].value;
        let beginDate = new Date(valueBegin);
        if ($(this.formHourTarget).prop('checked') == true){
            var subTime = 60;
        }
        else {
            var subTime = 15;
        }
        const valueEnd = $(this.formEndTarget).prop('value');
        let date = new Date(valueEnd);
        let newDate = new Date(date.getTime() - (subTime * 60000));
        if (newDate.getTime() > beginDate.getTime()) {
            if (newDate.getMonth() < 9) {
                var Month = '0'.concat((newDate.getMonth() + 1).toString());
            } else {
                var Month = (newDate.getMonth() + 1).toString();
            }
            if (newDate.getDate() < 10) {
                var Day = '0'.concat(newDate.getDate().toString());
            } else {
                var Day = newDate.getDate().toString();
            }

            if (newDate.getHours() < 10) {
                var hour = '0'.concat(newDate.getHours().toString());
            } else {
                var hour = newDate.getHours().toString();
            }
            if (newDate.getMinutes() < 10) {
                var minutes = '0'.concat(newDate.getMinutes().toString());
            } else {
                var minutes = newDate.getMinutes().toString();
            }

            let newStringdate = newDate.getFullYear().toString().concat('-', Month, '-', Day, 'T', hour, ':', minutes);
            $(this.formEndTarget).val(newStringdate);
            $(this.formEndDateTarget).val(newStringdate);
        }
    }
    hourCheck(){
        if ($(this.formBeginHiddenTarget).prop('value') == '' && $(this.formEndHiddenTarget).prop('value') == '') {
            $(this.formBeginHiddenTarget).val($(this.formBeginTarget).prop('value'));
            $(this.formEndHiddenTarget).val($(this.formEndTarget).prop('value'));
        }

        const valueBegin = $(this.formBeginDateTarget).prop('value');
        const valueEnd = $(this.formEndDateTarget).prop('value');
        var valueBeginHidden = $(this.formBeginHiddenTarget).prop('value');
        var valueEndHidden = $(this.formEndHiddenTarget).prop('value');

        if ($(this.formHourTarget).prop('checked') == true) {
            $(this.formBeginHiddenTarget).val(valueBegin);
            $(this.formEndHiddenTarget).val(valueEnd);
            //here begins the ruling to adjust the values to hours and also t oadd 0 in the dates if the are < 10
            let beginDate = new Date(valueBegin);
            let endDate = new Date(valueEnd);
            let beginMonth = '';
            let endMonth = '';
            let beginDay = '';
            let endDay = '';
            let beginHour = '';
            let endHour = '';

            let beginHourInt = 0;
            if (beginDate.getMinutes() < 15) {
               beginHourInt = beginDate.getHours() - 1;

            }
            else beginHourInt = beginDate.getHours();

            let hour = 0;
            if (endDate.getMinutes() > 15) hour = endDate.getHours() + 1;
            else hour = endDate.getHours();

            if (beginDate.getDate() < 10){
                beginDay =  '0'.concat(beginDate.getDate().toString());
            }
            else{
                beginDay = beginDate.getDate().toString();
            }
            if (endDate.getDate() < 10){
                endDay =  '0'.concat(endDate.getDate().toString());
            }
            else{
                endDay = endDate.getDate().toString();
            }
            if (beginDate.getMonth() < 9) {
                beginMonth = '0'.concat((beginDate.getMonth() + 1).toString());
            }
            else{
                beginMonth = (beginDate.getMonth() + 1).toString();
            }
            if (endDate.getMonth() < 9) {
                endMonth = '0'.concat((endDate.getMonth() + 1).toString());
            }
            else{
                endMonth = (endDate.getMonth() + 1).toString();
            }

            if (beginHourInt < 10){
                beginHour =  '0'.concat(beginHourInt.toString());
            }
            else{
                beginHour = beginHourInt.toString();
            }


            if (hour < 10){
                endHour = '0'.concat(hour.toString());
            }
            else{
                endHour =  hour.toString();
            }

            let newStringBeginDate = beginDate.getFullYear().toString().concat('-', beginMonth, '-', beginDay, 'T',beginHour, ':', '15');

            let newStringEndDate = endDate.getFullYear().toString().concat('-', endMonth, '-', endDay, 'T', endHour, ':', '15');

            $(this.formBeginDateTarget).val(newStringBeginDate);
            $(this.formEndDateTarget).val(newStringEndDate);
            $(this.formBeginTarget).val(newStringBeginDate);
            $(this.formEndTarget).val(newStringEndDate);
        }
        else {
            $(this.formBeginDateTarget).val(valueBeginHidden);
            $(this.formEndDateTarget).val(valueEndHidden);
            $(this.formBeginTarget).val(valueBeginHidden);
            $(this.formEndTarget).val(valueEndHidden);
        }

    }
    replaceCheck(){
        // this is the change of overlay if the user decides to replace energy with PVSYST in the replacement ticket
        let body = $(this.modalBodyTarget);

        if (this.formUrlValue === '/ticket/create') body.find('#ticket_form_KpiStatus').val(20);
        $(this.scopeTarget).removeClass('is-hidden');
            if ($(this.formReplaceTargets).prop('checked') == true) {
                $(this.headerReplaceIrrTargets).removeClass('is-hidden');
                $(this.headerHourTargets).removeClass('is-hidden');
                $(this.headerReplacePowerTargets).removeClass('is-hidden');
            }
            else if ($(this.formReplaceG4NTargets).prop('checked') == true) {
                $(this.headerReplacePowerG4NTargets).removeClass('is-hidden');
            }
            else{
                $(this.headerEnergyValueTargets).removeClass('is-hidden');
                $(this.headerIrrValueTargets).removeClass('is-hidden');
                $(this.headerReplacePowerG4NTargets).removeClass('is-hidden');
                $(this.headerHourTargets).removeClass('is-hidden');
                $(this.headerReplacePowerTargets).removeClass('is-hidden');
                $(this.headerHourTargets).removeClass('is-hidden');
            }
            $(this.headerReasonTargets).removeClass('is-hidden');
            $(this.headerFormKpiTargets).removeClass('is-hidden');
        if ($(this.formReplaceTargets).prop('checked') == true) {
            $(this.fieldReplaceIrrTargets).removeClass('is-hidden');
            $(this.fieldHourTargets).removeClass('is-hidden');
            $(this.fieldReplacePowerTargets).removeClass('is-hidden');
        }
        else if ($(this.formReplaceG4NTargets).prop('checked') == true) {
            $(this.fieldReplacePowerG4NTargets).removeClass('is-hidden');
        }
        else{
            $(this.fieldEnergyValueTargets).removeClass('is-hidden');
            $(this.fieldIrrValueTargets).removeClass('is-hidden');
            $(this.fieldReplacePowerTargets).removeClass('is-hidden');
            $(this.fieldHourTargets).removeClass('is-hidden');
            $(this.fieldReplacePowerG4NTargets).removeClass('is-hidden');
            $(this.fieldHourTargets).removeClass('is-hidden');
        }
            $(this.fieldReasonTargets).removeClass('is-hidden');
            $(this.formkpiStatusTargets).removeClass('is-hidden');
            if ($(this.formReplaceTarget).prop('checked') == true) {
                if ($(this.formHourTarget).prop('checked') == false) {
                    $(this.formHourTarget).prop('checked', true);
                    this.hourCheck();
                }
            }else if ($(this.formReplaceG4NTarget).prop('checked') == true){
                $(this.formHourTargets).prop('checked', false);
                this.hourCheck();
            }
            else{
                $(this.formHourTargets).prop('checked', false);
            }
        let reason = $(this.formReasonSelectTarget).val();
        $(this.reasonInputTarget).val(reason);
    }

    checkCategory(){
        const cat = $(this.formCategoryTarget).val();
        let inverterString = '';
        let inverterNameString = '';
        let body = $(this.modalBodyTarget);

        // in this switch we remove the 'is-hidden' class to show the field as of the ticket date depending on the category

        if (cat >= 70 && cat <= 80 ){
            body.find('input:checkbox[class=js-checkbox]').each(function () {
                $(this).prop('checked', true);
                body.find($('#div-split-'+$(this).prop('id')+'a')).removeClass('is-hidden');
                body.find($('#split-'+$(this).prop('id')+'a')).prop('checked', true);
                body.find($('#div-split-'+$(this).prop('id')+'b')).removeClass('is-hidden');
            });

            inverterString = '*';
            inverterNameString = '*';
            body.find('#ticket_form_inverter').val(inverterString);
            body.find('#ticket_form_inverterName').val(inverterNameString);
        }

        //first we hide everything to show only what we need depending on the category
        $(this.headerExcludeTargets).addClass('is-hidden');
        $(this.headerReplaceTargets).addClass('is-hidden');
        $(this.headerReplacePowerTargets).addClass('is-hidden');
        $(this.headerReplaceIrrTargets).addClass('is-hidden');
        $(this.headerHourTargets).addClass('is-hidden');
        $(this.headerEnergyValueTargets).addClass('is-hidden');
        $(this.headerIrrValueTargets).addClass('is-hidden');
        $(this.headerCorrectionTargets).addClass('is-hidden');
        $(this.headerEvaluationTargets).addClass('is-hidden');
        $(this.headerAktDep1Targets).addClass('is-hidden');
        $(this.headerReasonTargets).addClass('is-hidden');
        $(this.headerAktDep2Targets).addClass('is-hidden');
        $(this.headerAktDep3Targets).addClass('is-hidden');
        $(this.headerFormKpiTargets).addClass('is-hidden');
        $(this.headerPRMethodTargets).addClass('is-hidden');
        $(this.headerReplacePowerG4NTargets).addClass('is-hidden');

        $(this.fieldReplacePowerG4NTargets).addClass('is-hidden');
        $(this.fieldSensorTargets).addClass('is-hidden');
        $(this.fieldReplacePowerTargets).addClass('is-hidden');
        $(this.fieldReplaceIrrTargets).addClass('is-hidden');
        $(this.fieldHourTargets).addClass('is-hidden');
        $(this.fieldEnergyValueTargets).addClass('is-hidden');
        $(this.fieldIrrValueTargets).addClass('is-hidden');
        $(this.fieldCorrectionTargets).addClass('is-hidden');
        $(this.fieldReasonTargets).addClass('is-hidden');
        $(this.fieldEvaluationTargets).addClass('is-hidden');
        $(this.fieldAktDep1Targets).addClass('is-hidden');
        $(this.fieldAktDep2Targets).addClass('is-hidden');
        $(this.fieldAktDep3Targets).addClass('is-hidden');
        $(this.fieldPRMethodTargets).addClass('is-hidden');
        $(this.inverterDivTargets).addClass('is-hidden');
        $(this.formkpiStatusTargets).addClass('is-hidden');
        $(this.scopeTarget).addClass('is-hidden');
        switch (cat){
            case '10':
                $(this.headerEvaluationTargets).removeClass('is-hidden');
                $(this.headerAktDep1Targets).removeClass('is-hidden');
                $(this.headerAktDep2Targets).removeClass('is-hidden');
                $(this.headerAktDep3Targets).removeClass('is-hidden');

                $(this.fieldEvaluationTargets).removeClass('is-hidden');
                $(this.fieldAktDep1Targets).removeClass('is-hidden');
                $(this.fieldAktDep2Targets).removeClass('is-hidden');
                $(this.fieldAktDep3Targets).removeClass('is-hidden');
                $(this.inverterDivTargets).removeClass('is-hidden');

                $(this.formHourTargets).prop('checked', false);
                if (this.formUrlValue === '/ticket/create'){ body.find('#ticket_form_KpiStatus').val(20)};
                break;
            case '20':
                $(this.headerEvaluationTargets).removeClass('is-hidden');
                $(this.headerAktDep1Targets).removeClass('is-hidden');
                $(this.headerAktDep2Targets).removeClass('is-hidden');
                $(this.headerAktDep3Targets).removeClass('is-hidden');

                $(this.fieldEvaluationTargets).removeClass('is-hidden');
                $(this.fieldAktDep1Targets).removeClass('is-hidden');
                $(this.fieldAktDep2Targets).removeClass('is-hidden');
                $(this.fieldAktDep3Targets).removeClass('is-hidden');
                $(this.inverterDivTargets).removeClass('is-hidden');

                $(this.formHourTargets).prop('checked', false);
                if (this.formUrlValue === '/ticket/create'){ body.find('#ticket_form_KpiStatus').val(20)};
                break;
            case '40':
                body.find('input:checkbox[class=js-checkbox]').each(function () {
                    $(this).prop('checked', true);
                    body.find($('#div-split-'+$(this).prop('id')+'a')).removeClass('is-hidden');
                    body.find($('#split-'+$(this).prop('id')+'a')).prop('checked', true);
                    body.find($('#div-split-'+$(this).prop('id')+'b')).removeClass('is-hidden');
                });

                inverterString = '*';
                inverterNameString = '*';
                body.find('#ticket_form_inverter').val(inverterString);
                body.find('#ticket_form_inverterName').val(inverterNameString);
                $(this.formHourTargets).prop('checked', false);
                if (this.formUrlValue === '/ticket/create') {body.find('#ticket_form_KpiStatus').val(20)};
                break;
            case '50':
                body.find('input:checkbox[class=js-checkbox]').each(function () {
                    $(this).prop('checked', true);
                    body.find($('#div-split-'+$(this).prop('id')+'a')).removeClass('is-hidden');
                    body.find($('#split-'+$(this).prop('id')+'a')).prop('checked', true);
                    body.find($('#div-split-'+$(this).prop('id')+'b')).removeClass('is-hidden');
                });
                inverterString = '*';
                inverterNameString = '*';
                body.find('#ticket_form_inverter').val(inverterString);
                body.find('#ticket_form_inverterName').val(inverterNameString);
                $(this.formHourTargets).prop('checked', false);
                if (this.formUrlValue === '/ticket/create') {body.find('#ticket_form_KpiStatus').val(20)};
                break;

            case '60':
                body.find('input:checkbox[class=js-checkbox]').each(function () {
                    $(this).prop('checked', true);
                    body.find($('#div-split-'+$(this).prop('id')+'a')).removeClass('is-hidden');
                    body.find($('#split-'+$(this).prop('id')+'a')).prop('checked', true);
                    body.find($('#div-split-'+$(this).prop('id')+'b')).removeClass('is-hidden');
                });

                inverterString = '*';
                inverterNameString = '*';
                body.find('#ticket_form_inverter').val(inverterString);
                body.find('#ticket_form_inverterName').val(inverterNameString);
                $(this.formHourTargets).prop('checked', false);
                if (this.formUrlValue === '/ticket/create') {body.find('#ticket_form_KpiStatus').val(20)};
                break;
            case '70':
                $(this.headerExcludeTargets).removeClass('is-hidden');
                $(this.headerFormKpiTargets).removeClass('is-hidden');
                $(this.fieldSensorTargets).removeClass('is-hidden');
                $(this.formkpiStatusTargets).removeClass('is-hidden');
                $(this.formHourTargets).prop('checked', false);

                if (this.formUrlValue === '/ticket/create'){ body.find('#ticket_form_KpiStatus').val(10)};
                break;
            case '71':
                $(this.headerReplaceTargets).removeClass('is-hidden');
                $(this.headerFormKpiTargets).removeClass('is-hidden');

                $(this.fieldSensorTargets).removeClass('is-hidden');
                $(this.formkpiStatusTargets).removeClass('is-hidden');

                $(this.formHourTargets).prop('checked', false);

                if (this.formUrlValue === '/ticket/create') {body.find('#ticket_form_KpiStatus').val(20)};
                break;
            case '72':
                $(this.headerHourTargets).removeClass('is-hidden');
                $(this.headerFormKpiTargets).removeClass('is-hidden');
                $(this.headerPRMethodTargets).removeClass('is-hidden');

                $(this.fieldHourTargets).removeClass('is-hidden');
                $(this.fieldPRMethodTargets).removeClass('is-hidden');
                $(this.scopeTarget).removeClass('is-hidden');

                if (this.formUrlValue === '/ticket/create') {body.find('#ticket_form_KpiStatus').val(10)};
                break;
            case '73':
                this.replaceCheck();
                break;
            case '74':
                $(this.headerCorrectionTargets).removeClass('is-hidden');
                $(this.headerReasonTargets).removeClass('is-hidden');
                $(this.headerFormKpiTargets).removeClass('is-hidden');

                $(this.fieldCorrectionTargets).removeClass('is-hidden');
                $(this.fieldReasonTargets).removeClass('is-hidden');
                $(this.scopeTarget).removeClass('is-hidden');

                let reason = $(this.formReasonSelectTarget).val();
                $(this.reasonInputTarget).val(reason);

                if (this.formUrlValue === '/ticket/create') {body.find('#ticket_form_KpiStatus').val(20);}
                break;
            case '100':
                $(this.headerEvaluationTargets).removeClass('is-hidden');
                $(this.headerAktDep1Targets).removeClass('is-hidden');
                $(this.headerAktDep2Targets).removeClass('is-hidden');
                $(this.headerAktDep3Targets).removeClass('is-hidden');

                $(this.fieldEvaluationTargets).removeClass('is-hidden');
                $(this.fieldAktDep1Targets).removeClass('is-hidden');
                $(this.fieldAktDep2Targets).removeClass('is-hidden');
                $(this.fieldAktDep3Targets).removeClass('is-hidden');

                $(this.formHourTargets).prop('checked', false);
                if (this.formUrlValue === '/ticket/create'){ body.find('#ticket_form_KpiStatus').val(20)};
                break;
            case '':
                $(this.formHourTarget).prop('checked', false);
                if (this.formUrlValue === '/ticket/create') {body.find('#ticket_form_KpiStatus').val(20);}
                break;

            default:
                $(this.inverterDivTargets).removeClass('is-hidden');
                $(this.formHourTargets).prop('checked', false);

                if (this.formUrlValue === '/ticket/create') {body.find('#ticket_form_KpiStatus').val(20)};
        }
    }

    setBody(html){
        this.modalBodyTarget.innerHTML = html;
    }

    closeTicket(event) {
        event.preventDefault();
        this.dispatch('success');
        this.modal.destroy();
    }

    closeContact(event) {
        event.preventDefault();
        this.contactModal.destroy();
    }
    closeContactCreate(event) {
        event.preventDefault();
        this.contactCreateModal.destroy();
    }
    closeTimeline(event){
        event.preventDefault();
        this.timelineModal.destroy();
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
    closeNotify(event) {
        event.preventDefault();

    }
    async reload(event){
        this.modalBodyTarget.innerHTML = await $.ajax(this.formUrlValue);
        this.checkCategory();
        $(document).foundation();
    }
    async contact() {
        const $form = $(this.contactModalTarget).find('form');
        try {
            await $.ajax({
                url: this.notifyUrlValue,
                method: $form.prop('method'),
                data: $form.serialize(),
            });
            this.contactModal.destroy();
        } catch (e) {
            this.modalContactBodyTarget.innerHTML = e.responseText;
        }
    }
    async saveNewContact(){
        const  $form = $(this.contactModalCreateTarget).find('form');
        try {
            await $.ajax({
                url: this.createContactUrlValue,
                method: $form.prop('method'),
                data: $form.serialize(),
            });
            this.contactCreateModal.destroy();
            this.contactModal.destroy();
            this.openContactModal();
            }catch(e) {
            this.modalContactCreateBodyTarget.innerHTML = e.responseText;
        }
    }
    checkTrafo({ params: { first, last, trafo }}){

        let body = $(this.modalBodyTarget);
        let checked = $("#trafo" + trafo).prop('checked');
        body.find('input:checkbox[class=js-checkbox]').each(function (){
            if ($(this).prop('id').substring(2) >= first) {
                if ($(this).prop('id').substring(2) <= last){
                    if (checked) $(this).prop('checked', true);
                    else $(this).prop('checked', false);
                }
            }
        });
        $(this.switchTarget).prop('checked', false)
        this.saveCheck();
    }

    checkSelect({ params: { edited }}){
        const cat = $(this.formCategoryTarget).val();
        const valueBegin = $(this.formBeginTarget).prop('value');
        const valueEnd = $(this.formEndTarget).prop('value');
        let body = $(this.modalBodyTarget);
        const date1 = new Date(valueBegin);
        const date2 = new Date(valueEnd);
        date1.setSeconds(0);
        date2.setSeconds(0);
        const timestamp1 = date1.getTime();
        const timestamp2 = date2.getTime();
        body.find('.js-div-split-a').each(function(){
            $(this).addClass('is-hidden');
            $(this).find('.js-checkbox-split-a').prop('checked', false);
        });
        body.find('.js-div-split-b').each(function(){
            $(this).addClass('is-hidden');
            $(this).find('.js-checkbox-split-b').prop('checked', false);
        });
        let inverterString = '';
        let inverterNameString = '';
        console.log($(this.switchTarget).prop('checked'));
        if ($(this.switchTarget).prop('checked')) {
            
            body.find('input:checkbox[class=js-checkbox-trafo]').each(function () {
                $(this).prop('checked', true);
            });
            body.find('input:checkbox[class=js-multiselect-checkbox]').each(function () {
                $(this).prop('checked', true);
                body.find($('#div-split-'+$(this).prop('id')+'a')).removeClass('is-hidden');
                body.find($('#split-'+$(this).prop('id')+'a')).prop('checked', true);
                body.find($('#div-split-'+$(this).prop('id')+'b')).removeClass('is-hidden');
            });
            $(this.modalBodyTarget).find('input:checkbox[class=js-checkbox]').each(function(){
                $(this).prop('checked', true);
            });
            if (edited == true) {
                $(this.splitDeployTarget).removeAttr('disabled');
            }
            inverterString = '*';
            inverterNameString = '*';
        } else {
            body.find('input:checkbox[class=js-checkbox-trafo]').each(function () {
                $(this).prop('checked', false);
            });
            $(this.modalBodyTarget).find('input:checkbox[class=js-checkbox]').each(function(){
                $(this).prop('checked', false);
            });
            $(this.splitDeployTarget).attr('disabled', 'disabled');
            inverterString = '';
            inverterNameString = '';
        }
        $(this.modalBodyTarget).find('#ticket_form_inverter').val(inverterString);
        body.find('#ticket_form_inverterName').val(inverterNameString);
        if (inverterString == '') {
            $(this.CalloutTarget).removeClass('is-hidden');
            $(this.AlertInverterTarget).removeClass('is-hidden');
            $(this.saveButtonTarget).attr('disabled', 'disabled');

            if (timestamp2 < timestamp1){
                $(this.AlertDatesTarget).removeClass('is-hidden');
                $(this.saveButtonTarget).attr('disabled', 'disabled');
                if ((timestamp1 % 900000 != 0) && (timestamp2 % 900000 != 0)){
                    $(this.AlertFormatTarget).addClass('is-hidden');
                } else {
                    $(this.AlertFormatTarget).removeClass('is-hidden');
                }
            }

            if (cat == ""){

                $(this.AlertCategoryTarget).removeClass('is-hidden');
            }
            else{
                $(this.AlertCategoryTarget).addClass('is-hidden');
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
                    $(this.saveButtonTarget).removeAttr('disabled');
                    if (cat == ''){
                        $(this.CalloutTarget).removeClass('is-hidden');
                        $(this.saveButtonTarget).attr('disabled', 'disabled');
                        $(this.AlertCategoryTarget).removeClass('is-hidden');
                    }
                    else{
                        $(this.AlertCategoryTarget).addClass('is-hidden');
                    }
                } else {
                    $(this.CalloutTarget).removeClass('is-hidden');
                    $(this.AlertFormatTarget).removeClass('is-hidden');
                    $(this.saveButtonTarget).attr('disabled', 'disabled');
                    if (cat == ""){
                        $(this.saveButtonTarget).attr('disabled', 'disabled');
                    }
                    else{
                        $(this.AlertCategoryTarget).addClass('is-hidden');
                    }
                }
            } else {
                $(this.CalloutTarget).removeClass('is-hidden')
                $(this.AlertDatesTarget).removeClass('is-hidden');
                $(this.saveButtonTarget).attr('disabled', 'disabled');
                if (cat == ""){
                    $(this.AlertCategoryTarget).removeClass('is-hidden');
                }
                else{
                    $(this.AlertCategoryTarget).addClass('is-hidden');
                }
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

    setHiddenValue(){
        this.formBeginTarget.dataset.originalValue = $(this.formBeginTarget).prop('value');
        this.formEndTarget.dataset.originalValue = $(this.formEndTarget).prop('value');
    }

    beginCheck(event){
        event.prevent
        if ($(this.formBeginTarget).prop('value') != this.formBeginTarget.dataset.originalValue) {
            const valueBegin = $(this.formBeginTarget).prop('value');
            const date = new Date(valueBegin);
            date.setSeconds(0);
            const timestamp1 = date.getTime();
            if (timestamp1 % 300000 == 0) {
                var hour = date.getHours();
                var minutes = date.getMinutes();
                switch (minutes) {

                    case 5:
                    case 10:
                    case 15:
                        minutes = 15;
                        break;
                    case 20:
                    case 25:
                    case 30:
                        minutes = 30;
                        break;
                    case 35:
                    case 40:
                    case 45:
                        minutes = 45;
                        break;
                    case 50:
                    case 55:
                        hour = hour + 1;
                        minutes = 0;
                        break;
                    case 0:
                        minutes = 0;
                        break;
                }

                if (date.getMonth() < 9) {
                    var Month = '0'.concat((date.getMonth() + 1).toString());
                } else {
                    var Month = (date.getMonth() + 1).toString();
                }
                if (date.getDate() < 10) {
                    var Day = '0'.concat(date.getDate().toString());
                } else {
                    var Day = date.getDate().toString();
                }
                if (hour < 10) {
                    hour = '0'.concat(hour.toString());
                }
                if (minutes < 10) {
                    minutes = '0'.concat(minutes.toString());
                }
                let newStringdate = date.getFullYear().toString().concat('-', Month, '-', Day, 'T', hour, ':', minutes);
                if ($(this.formEndTarget).prop('value') == ""){
                    const timestampEnd = date.getTime() + 900000;
                    const endDate = new Date(timestampEnd);
                    var hour = endDate.getHours();
                    var minutes = endDate.getMinutes();

                    if (endDate.getMonth() < 9) {
                        var Month = '0'.concat((endDate.getMonth() + 1).toString());
                    } else {
                        var Month = (endDate.getMonth() + 1).toString();
                    }
                    if (endDate.getDate() < 10) {
                        var Day = '0'.concat(endDate.getDate().toString());
                    } else {
                        var Day = endDate.getDate().toString();
                    }
                    if (hour < 10) {
                        hour = '0'.concat(hour.toString());
                    }
                    if (minutes < 10) {
                        minutes = '0'.concat(minutes.toString());
                    }
                    let endDateString = endDate.getFullYear().toString().concat('-', Month, '-', Day, 'T', hour, ':', minutes);$(this.formEndTarget).val(endDateString);
                    $(this.formEndDateTarget).val(endDateString);
                }
                $(this.formBeginTarget).val(newStringdate);
                $(this.formBeginDateTarget).val(newStringdate);
            }
        }


       this.saveCheck();
    }
    endCheck()
        {
            if ($(this.formEndTarget).prop('value') != this.formEndTarget.dataset.originalValue) {
                const valueEnd = $(this.formEndTarget).prop('value');
                const date = new Date(valueEnd);
                date.setSeconds(0);
                const timestamp1 = date.getTime();
                if (timestamp1 % 300000 == 0) {
                    var hour = date.getHours();
                    var minutes = date.getMinutes();

                    switch (minutes) {

                        case 5:
                        case 10:
                        case 15:
                            minutes = 15;
                            break;
                        case 20:
                        case 25:
                        case 30:
                            minutes = 30;
                            break;
                        case 35:
                        case 40:
                        case 45:
                            minutes = 45;
                            break;
                        case 50:
                        case 55:
                            hour = hour + 1;
                            minutes = 0;
                            break;
                        case 0:
                            minutes = 0;
                            break;
                    }
                    if (date.getMonth() < 9) {
                        var Month = '0'.concat((date.getMonth() + 1).toString());
                    } else {
                        var Month = (date.getMonth() + 1).toString();
                    }
                    if (date.getDate() < 10) {
                        var Day = '0'.concat(date.getDate().toString());
                    } else {
                        var Day = date.getDate().toString();
                    }
                    if (hour < 10) {
                        hour = '0'.concat(hour.toString());
                    }
                    if (minutes < 10) {
                        minutes = '0'.concat(minutes.toString());
                    }
                    let newStringdate = date.getFullYear().toString().concat('-', Month, '-', Day, 'T', hour, ':', minutes);
                    $(this.formEndTarget).val(newStringdate);
                    $(this.formEndDateTarget).val(newStringdate);
                }
            }

        this.saveCheck();
    }
    disableSave(){
        $(this.saveButtonTarget).attr('disabled', 'disabled');
        $(this.CalloutTarget).removeClass('is-hidden');
        $(this.AlertInverterSubmitTarget).removeClass('is-hidden');
    }
    submitInverters(event){
        event.preventDefault();
        $(this.AlertInverterSubmitTarget).addClass('is-hidden');
        this.saveCheck();
    }
    saveCheck(){
        //event.preventDefault();
        //getting a string with the inverters so later we can check if there is any or none
        let inverterString = '';
        let inverterNameString = '';
        let body = $(this.modalBodyTarget);
        let counter = 0;
        this.checkCategory()
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
            if (inverterString == '') {
                inverterString = inverterString + $(this).prop('id').substring(2);
                inverterNameString = inverterNameString + $(this).prop('name');
            }
            else {
                inverterString = inverterString + ', ' + $(this).prop('id').substring(2);
                inverterNameString = inverterNameString + ', ' + $(this).prop('name');
            }
            body.find($('#div-split-'+$(this).prop('id')+'a')).removeClass('is-hidden');
            body.find($('#div-split-'+$(this).prop('id')+'b')).removeClass('is-hidden');
            body.find($('#split-'+$(this).prop('id')+'a')).prop('checked', true);

        });
        if (counter == body.find('input:checkbox[class=js-checkbox]').length){
            inverterString = '*';
            inverterNameString = '*';
        }

        let sensorString = '';


        body.find('input:checkbox[class=sensor-checkbox]:checked').each(function (){
            if (sensorString == '') {sensorString = sensorString + $(this).prop('name');}
            else {sensorString = sensorString + ', ' + $(this).prop('name');}
        });
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
            $(this.splitDeployTarget).removeAttr('disabled');
        }
        if (inverterString == '') {
            $(this.CalloutTarget).removeClass('is-hidden');
            $(this.AlertInverterTarget).removeClass('is-hidden');
            $(this.saveButtonTarget).attr('disabled', 'disabled');
            if (timestamp2 > timestamp1){
                $(this.AlertDatesTarget).addClass('is-hidden');
                if ((timestamp1 % 300000 == 0) && (timestamp2 % 300000 == 0)){
                    $(this.AlertFormatTarget).addClass('is-hidden');
                    if ($(this.formHourTarget).prop('checked') == true) this.hourCheck();
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
                if ((timestamp1 % 300000 == 0) && (timestamp2 % 300000 == 0)){
                    if ($(this.formHourTarget).prop('checked') == true) this.hourCheck();
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
                if ((timestamp1 % 300000 == 0) && (timestamp2 % 300000 == 0)){
                    if ($(this.formHourTarget).prop('checked') == true) this.hourCheck();
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

                    if ((timestamp1 % 300000 == 0) && (timestamp2 % 300000 == 0)){
                        if ($(this.formHourTarget).prop('checked') == true) this.hourCheck();
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
        body.find('#ticket_form_inverterName').val(inverterNameString);
        $(this.modalBodyTarget).find('#ticket_form_dates_0_sensors').val(sensorString);
    }


    toggle(){

        let $button = $(this.deactivableTarget);
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