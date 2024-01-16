import { Controller } from '@hotwired/stimulus';
import { useDispatch } from 'stimulus-use';
import { Reveal } from 'foundation-sites';
import $ from 'jquery';

export default class extends Controller {
    static targets = ['splitAlert', 'modal', 'modalBody', 'splitModal', 'splitForm', 'switch', 'deactivable',
                        'anlage', 'saveButton', 'AlertFormat', 'AlertDates', 'formBegin', 'formEnd', 'splitButton',
                        'splitDeploy','AlertInverter', 'Callout', 'formCategory', 'AlertCategory', 'headerExclude',
                        'headerReplace', 'headerReplacePower', 'headerReplaceIrr', 'headerHour', 'headerEnergyValue',
                        'headerIrrValue', 'headerCorrection', 'headerEvaluation', 'headerAktDep1', 'headerAktDep2',
                        'headerAktDep3', 'formReplace', 'fieldSensor', 'fieldReplacePower', 'fieldReplaceIrr', 'fieldHour',
                        'fieldEnergyValue', 'fieldIrrValue', 'fieldCorrection', 'fieldEvaluation', 'fieldAktDep1', 'fieldAktDep2',
                        'fieldAktDep3', 'formReplaceIrr', 'inverterDiv', 'formHour', 'formBeginHidden', 'formEndHidden', 'formBeginDate',
                        'formEndDate', 'formReasonSelect', 'formReasonText', 'headerReason', 'fieldReason', 'formkpiStatus', 'headerFormKpi',
                        'headerPRMethod', 'fieldPRMethod', 'scope', 'reasonInput', 'sensorDiv', 'contactModal', 'contactButton', 'modalContactBody'];
    static values = {
        formUrl: String,
        splitUrl: String,
        notifyUrl: String,
    }
    modal = null;
    splitModal = null;
    contactModal = null;

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
        event.preventDefault();
        this.modalContactBodyTarget.innerHTML = 'Loading ...';
        this.contactModal = new Reveal($(this.contactModalTarget));
        this.contactModal.open();
        console.log(this.notifyUrlValue);
        this.modalContactBodyTarget.innerHTML = await $.ajax({
            url: this.notifyUrlValue,
        });
    }


    reasonCheck(){
        let reason = $(this.reasonInputTarget).val();
        $(this.formReasonSelectTarget).val(reason);
    }

    beginPlusTime(){
        let hour;
        const valueBegin = $(this.formBeginTarget).prop('value');
        const valueEnd = $(this.formEndTarget).prop('value');
        let date = new Date(valueBegin);
        let date2 = new Date(valueEnd);
        if (date.getTime() + 900000 < date2.getTime()) {
            if ($(this.formHourTarget).prop('checked') == true) {

                if (date.getHours() < date2.getHours() - 1){

                    hour = date.getHours() + 1;
                    var minutes = '15';
                }
                else{
                    hour = date.getHours();
                    var minutes = '15';
                }
            }
            else {
                if (date.getMinutes() + 15 == 60) {
                    hour = date.getHours() + 1;
                    var minutes = '00';
                } else {
                    hour = date.getHours();
                    var minutes = date.getMinutes() + 15;
                }
            }
            if (date.getMonth() < 9) {
                var beginMonth = '0'.concat((date.getMonth() + 1).toString());
            } else {
                var beginMonth = (date.getMonth() + 1).toString();
            }
            if (date.getDate() < 10) {
                var beginDay = '0'.concat(date.getDate().toString());
            } else {
                var beginDay = date.getDate().toString();
            }
            if (hour < 10) {
                hour = '0'.concat(hour.toString());
            }
            let newStringdate = date.getFullYear().toString().concat('-', beginMonth, '-', beginDay, 'T', hour, ':', minutes.toString());
            $(this.formBeginTarget).val(newStringdate);
            $(this.formBeginDateTarget).val(newStringdate);
            if ($(this.formHourTarget).prop('checked') == true) this.hourCheck();

        }
    }
    beginMinusTime(){
        const valueBegin = $(this.formBeginTarget).prop('value');
        let date = new Date(valueBegin);

        if (date.getMinutes() - 15 < 0){
            var hour = date.getHours() -1;
            var minutes = '45';
        }
        else {
            var hour = date.getHours();
            if (date.getMinutes() - 15 == 0) var minutes = '00';
            else var minutes = date.getMinutes() - 15;
        }

        if (date.getMonth() < 9) {
            var beginMonth = '0'.concat((date.getMonth() + 1).toString());
        }
        else{
            var beginMonth = (date.getMonth() + 1).toString();
        }
        if (date.getDate() < 10){
            var beginDay =  '0'.concat(date.getDate().toString());
        }
        else{
            var beginDay = date.getDate().toString();
        }

        if (hour < 10){
            var hour =  '0'.concat(hour.toString());
        }

        let newStringdate = date.getFullYear().toString().concat('-', beginMonth, '-', beginDay, 'T', hour, ':', minutes.toString());
        $(this.formBeginTarget).val(newStringdate);
        $(this.formBeginDateTarget).val(newStringdate);
        if ($(this.formHourTarget).prop('checked') == true)this.hourCheck();
    }
    endPlusTime(){
        const valueEnd = $(this.formEndTarget).prop('value');
        let date = new Date(valueEnd);

        if (date.getMinutes() + 15 == 60){
            var hour = date.getHours() + 1;
            var minutes = '00';
        }
        else {
            var hour = date.getHours();
            var minutes = date.getMinutes() + 15;
        }



        if (date.getMonth() < 9) {
            var beginMonth = '0'.concat((date.getMonth() + 1).toString());
        }
        else{
            var beginMonth = (date.getMonth() + 1).toString();
        }
        if (date.getDate() < 10){
            var beginDay =  '0'.concat(date.getDate().toString());
        }
        else{
            var beginDay = date.getDate().toString();
        }

        if (hour < 10){
            var hour =  '0'.concat(hour.toString());
        }

        let newStringdate = date.getFullYear().toString().concat('-', beginMonth, '-', beginDay, 'T', hour, ':', minutes.toString());
        $(this.formEndTarget).val(newStringdate);
        $(this.formEndDateTarget).val(newStringdate);
        if ($(this.formHourTarget).prop('checked') == true)this.hourCheck();
    }
    endMinusTime(){
        const valueEnd = $(this.formEndTarget).prop('value');
        const valueBegin = $(this.formBeginTarget).prop('value');
        let date = new Date(valueEnd);
        let date2 = new Date(valueBegin);

        if (date.getTime() - 900000 > date2.getTime()) {
            if ($(this.formHourTarget).prop('checked') == true){
                if (date.getHours() > date2.getHours() + 1) {
                    var hour = date.getHours() - 1;
                    var minutes = '15';
                }
                else{
                    var hour = date.getHours();
                    var minutes = '15';
                }
            }
            else{
                if (date.getMinutes() - 15 < 0) {
                    var hour = date.getHours() - 1;
                    var minutes = '45';
                }
                else {
                var hour = date.getHours();
                    if (date.getMinutes() - 15 == 0) var minutes = '00';
                    else var minutes = date.getMinutes() - 15;
                }
            }


            if (date.getMonth() < 9) {
                var beginMonth = '0'.concat((date.getMonth() + 1).toString());
            } else {
                var beginMonth = (date.getMonth() + 1).toString();
            }
            if (date.getDate() < 10) {
                var beginDay = '0'.concat(date.getDate().toString());
            } else {
                var beginDay = date.getDate().toString();
            }

            if (hour < 10) {
                var hour = '0'.concat(hour.toString());
            }

            let newStringdate = date.getFullYear().toString().concat('-', beginMonth, '-', beginDay, 'T', hour, ':', minutes.toString());
            $(this.formEndTarget).val(newStringdate);
            $(this.formEndDateTarget).val(newStringdate);
            if ($(this.formHourTarget).prop('checked') == true)this.hourCheck();
        }
    }
    hourCheck(){

        if ($(this.formReplaceTarget).prop('checked') == true) $(this.formHourTarget).prop('checked', true)
        //NEW BEHAVIOUR: if the category is one of the performance ticket, the hour Check must also be respected for the main dates
        //for that we will check if the category is one of those and if true, we will make ticket date begin/end = mainticket begin/end
        //after that if hour is checked we will also aply the hour rule to the main ticket date
        const cat = $(this.formCategoryTarget).val();
        if (cat >= 70 && cat < 80){
            $(this.formBeginDateTarget).val($(this.formBeginTarget).prop('value'));
            $(this.formEndDateTarget).val($(this.formEndTarget).prop('value'));
        }
        const valueBegin = $(this.formBeginDateTarget).prop('value');
        const valueEnd = $(this.formEndDateTarget).prop('value');
        const valueBeginHidden = $(this.formBeginHiddenTarget).prop('value');
        const valueEndHidden = $(this.formEndHiddenTarget).prop('value');
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
            let beginHourInt = 0;
            if (beginDate.getMinutes() < 15) beginHourInt = beginDate.getHours() - 1;
            else beginHourInt = beginDate.getHours();

            let hour = 0;
            if (endDate.getMinutes() > 15) hour = endDate.getHours() + 1;
            else hour = endDate.getHours();

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
        else if(valueBeginHidden != '' && valueEndHidden != ''){
            $(this.formBeginDateTarget).val(valueBeginHidden);
            $(this.formEndDateTarget).val(valueEndHidden);
            $(this.formBeginTarget).val(valueBeginHidden);
            $(this.formEndTarget).val(valueEndHidden);
        }
        //now we recheck the dates
        const date1 = new Date($(this.formBeginTarget).prop('value'));
        const date2 = new Date($(this.formEndTarget).prop('value'));
        date1.setSeconds(0);
        date2.setSeconds(0);
        const timestamp1 = date1.getTime();
        const timestamp2 = date2.getTime();

        if (timestamp2 > timestamp1) {
            $(this.CalloutTarget).addClass('is-hidden');
            $(this.saveButtonTarget).removeAttr('disabled');
            $(this.AlertDatesTarget).addClass('is-hidden');
            if ((timestamp1 % 900000 == 0) && (timestamp2 % 900000 == 0)){
                $(this.AlertFormatTarget).addClass('is-hidden');
            } else {
                $(this.CalloutTarget).removeClass('is-hidden');
                $(this.AlertFormatTarget).removeClass('is-hidden');
                $(this.saveButtonTarget).attr('disabled', 'disabled');
            }
        }
        else{
            $(this.CalloutTarget).removeClass('is-hidden');
            $(this.saveButtonTarget).attr('disabled', 'disabled');
            $(this.AlertDatesTarget).removeClass('is-hidden');
            if ((timestamp1 % 900000 == 0) && (timestamp2 % 900000 == 0)){
                $(this.AlertFormatTarget).addClass('is-hidden');
            } else {
                $(this.AlertFormatTarget).removeClass('is-hidden');
            }
        }


    }
    replaceCheck(){

// this is the change of overlay if the user decides to replace energy with PVSYST in the replace ticket
        let body = $(this.modalBodyTarget);
            if (this.formUrlValue === '/ticket/create'){ body.find('#ticket_form_KpiStatus').val(20);}

            $(this.headerExcludeTargets).addClass('is-hidden');
            $(this.headerReplaceTargets).addClass('is-hidden');
            $(this.headerReplacePowerTargets).removeClass('is-hidden');
            if ($(this.formReplaceTargets).prop('checked') == false) {
                $(this.headerReplaceIrrTargets).addClass('is-hidden');
                $(this.headerEnergyValueTargets).removeClass('is-hidden');
                $(this.headerIrrValueTargets).removeClass('is-hidden');
            }
            else{
                $(this.headerReplaceIrrTargets).removeClass('is-hidden');
                //$(this.headerHourTarget).addClass('is-hidden');
                $(this.headerEnergyValueTargets).addClass('is-hidden');
                $(this.headerIrrValueTargets).addClass('is-hidden');
            }
            $(this.headerHourTargets).removeClass('is-hidden');
            $(this.headerCorrectionTargets).addClass('is-hidden');
            $(this.headerEvaluationTargets).addClass('is-hidden');
            $(this.headerReasonTargets).removeClass('is-hidden');
            $(this.headerAktDep1Targets).addClass('is-hidden');
            $(this.headerAktDep2Targets).addClass('is-hidden');
            $(this.headerAktDep3Targets).addClass('is-hidden');
            $(this.headerFormKpiTargets).removeClass('is-hidden');
            $(this.headerPRMethodTargets).addClass('is-hidden');

            $(this.fieldSensorTargets).addClass('is-hidden');
            $(this.fieldReplacePowerTargets).removeClass('is-hidden');
            if ($(this.formReplaceTargets).prop('checked') == false) {
                $(this.fieldReplaceIrrTargets).addClass('is-hidden');
                $(this.fieldEnergyValueTargets).removeClass('is-hidden');
                $(this.fieldIrrValueTargets).removeClass('is-hidden');
            }
            else{
                $(this.fieldReplaceIrrTargets).removeClass('is-hidden');
                $(this.fieldEnergyValueTargets).addClass('is-hidden');
                $(this.fieldIrrValueTargets).addClass('is-hidden');
            }
            $(this.inverterDivTargets).addClass('is-hidden');
            $(this.fieldHourTargets).removeClass('is-hidden');
            $(this.fieldCorrectionTargets).addClass('is-hidden');
            $(this.fieldEvaluationTargets).addClass('is-hidden');
            $(this.fieldReasonTargets).removeClass('is-hidden');
            $(this.fieldAktDep1Targets).addClass('is-hidden');
            $(this.fieldAktDep2Targets).addClass('is-hidden');
            $(this.fieldAktDep3Targets).addClass('is-hidden');
            $(this.formkpiStatusTargets).removeClass('is-hidden');
            $(this.fieldPRMethodTargets).addClass('is-hidden');
            if ($(this.formReplaceTarget).prop('checked') == true) {
                if ($(this.formHourTarget).prop('checked') == false) {
                    $(this.formHourTarget).prop('checked', true);
                    this.hourCheck();
                }
            }
        let reason = $(this.formReasonSelectTarget).val();
        $(this.reasonInputTarget).val(reason);
    }
    checkCategory(){
        const cat = $(this.formCategoryTarget).val();
        var inverterString = '';
        var inverterNameString = '';
        let body = $(this.modalBodyTarget);
        // in this switch we remove the hidding class to show the fields of the ticket date on demand

        if (cat >= 70 && cat <= 80 ){
            body.find('input:checkbox[class=js-checkbox]').each(function () {
                $(this).prop('checked', true);
                if (inverterString == '') {
                    inverterString = inverterString + $(this).prop('name');
                    inverterNameString = inverterNameString + $(this).prop('id');
                } else {
                    inverterString = inverterString + ', ' + $(this).prop('name');
                    inverterNameString = inverterNameString + ', ' + $(this).prop('id');
                }
                body.find($('#div-split-'+$(this).prop('name')+'a')).removeClass('is-hidden');
                body.find($('#split-'+$(this).prop('name')+'a')).prop('checked', true);
                body.find($('#div-split-'+$(this).prop('name')+'b')).removeClass('is-hidden');
            });

            inverterString = '*';
            body.find('#ticket_form_inverter').val(inverterString);
            body.find('#ticket_form_inverterName').val(inverterNameString);
        }

        if (cat >= 72 && cat <= 80 ){
            $(this.scopeTarget).removeClass('is-hidden');
        }
        else  $(this.scopeTarget).addClass('is-hidden');

        switch (cat){
            case '10':
                $(this.headerExcludeTargets).addClass('is-hidden');
                $(this.headerReplaceTargets).addClass('is-hidden');
                $(this.headerReplacePowerTargets).addClass('is-hidden');
                $(this.headerReplaceIrrTargets).addClass('is-hidden');
                $(this.headerHourTargets).addClass('is-hidden');
                $(this.headerEnergyValueTargets).addClass('is-hidden');
                $(this.headerIrrValueTargets).addClass('is-hidden');
                $(this.headerCorrectionTargets).addClass('is-hidden');
                $(this.headerEvaluationTargets).removeClass('is-hidden');
                $(this.headerReasonTargets).addClass('is-hidden');
                $(this.headerAktDep1Targets).removeClass('is-hidden');
                $(this.headerAktDep2Targets).removeClass('is-hidden');
                $(this.headerAktDep3Targets).removeClass('is-hidden');
                $(this.headerFormKpiTargets).addClass('is-hidden');
                $(this.headerPRMethodTargets).addClass('is-hidden');

                $(this.fieldSensorTargets).addClass('is-hidden');
                $(this.fieldReplacePowerTargets).addClass('is-hidden');
                $(this.fieldReplaceIrrTargets).addClass('is-hidden');
                $(this.fieldHourTargets).addClass('is-hidden');
                $(this.fieldEnergyValueTargets).addClass('is-hidden');
                $(this.fieldIrrValueTargets).addClass('is-hidden');
                $(this.fieldCorrectionTargets).addClass('is-hidden');
                $(this.fieldReasonTargets).addClass('is-hidden');
                $(this.fieldEvaluationTargets).removeClass('is-hidden');
                $(this.fieldAktDep1Targets).removeClass('is-hidden');
                $(this.fieldAktDep2Targets).removeClass('is-hidden');
                $(this.fieldAktDep3Targets).removeClass('is-hidden');
                $(this.inverterDivTargets).removeClass('is-hidden');
                $(this.formHourTargets).prop('checked', false);
                $(this.formkpiStatusTargets).addClass('is-hidden');
                $(this.fieldPRMethodTargets).addClass('is-hidden');
                if (this.formUrlValue === '/ticket/create'){ body.find('#ticket_form_KpiStatus').val(20)};
                break;
            case '20':
                $(this.headerExcludeTargets).addClass('is-hidden');
                $(this.headerReplaceTargets).addClass('is-hidden');
                $(this.headerReplacePowerTargets).addClass('is-hidden');
                $(this.headerReplaceIrrTargets).addClass('is-hidden');
                $(this.headerHourTargets).addClass('is-hidden');
                $(this.headerEnergyValueTargets).addClass('is-hidden');
                $(this.headerIrrValueTargets).addClass('is-hidden');
                $(this.headerCorrectionTargets).addClass('is-hidden');
                $(this.headerEvaluationTargets).removeClass('is-hidden');
                $(this.headerReasonTargets).addClass('is-hidden');
                $(this.headerAktDep1Targets).removeClass('is-hidden');
                $(this.headerAktDep2Targets).removeClass('is-hidden');
                $(this.headerAktDep3Targets).removeClass('is-hidden');
                $(this.headerFormKpiTargets).addClass('is-hidden');
                $(this.headerPRMethodTargets).addClass('is-hidden');

                $(this.fieldSensorTargets).addClass('is-hidden');
                $(this.fieldReplacePowerTargets).addClass('is-hidden');
                $(this.fieldReplaceIrrTargets).addClass('is-hidden');
                $(this.fieldHourTargets).addClass('is-hidden');
                $(this.fieldEnergyValueTargets).addClass('is-hidden');
                $(this.fieldIrrValueTargets).addClass('is-hidden');
                $(this.fieldCorrectionTargets).addClass('is-hidden');
                $(this.fieldEvaluationTargets).removeClass('is-hidden');
                $(this.fieldReasonTargets).addClass('is-hidden');
                $(this.fieldAktDep1Targets).removeClass('is-hidden');
                $(this.fieldAktDep2Targets).removeClass('is-hidden');
                $(this.fieldAktDep3Targets).removeClass('is-hidden');
                $(this.inverterDivTargets).removeClass('is-hidden');

                $(this.formHourTargets).prop('checked', false);
                $(this.formkpiStatusTargets).addClass('is-hidden');
                $(this.fieldPRMethodTargets).addClass('is-hidden');
                if (this.formUrlValue === '/ticket/create'){ body.find('#ticket_form_KpiStatus').val(20)};
                break;
            case '70':
                $(this.headerExcludeTargets).removeClass('is-hidden');
                $(this.headerReplaceTargets).addClass('is-hidden');
                $(this.headerReplacePowerTargets).addClass('is-hidden');
                $(this.headerReplaceIrrTargets).addClass('is-hidden');
                $(this.headerHourTargets).addClass('is-hidden');
                $(this.headerEnergyValueTargets).addClass('is-hidden');
                $(this.headerIrrValueTargets).addClass('is-hidden');
                $(this.headerCorrectionTargets).addClass('is-hidden');
                $(this.headerEvaluationTargets).addClass('is-hidden');
                $(this.headerReasonTargets).addClass('is-hidden');
                $(this.headerAktDep1Targets).addClass('is-hidden');
                $(this.headerAktDep2Targets).addClass('is-hidden');
                $(this.headerAktDep3Targets).addClass('is-hidden');
                $(this.headerFormKpiTargets).removeClass('is-hidden');
                $(this.headerPRMethodTargets).addClass('is-hidden');

                $(this.fieldSensorTargets).removeClass('is-hidden');
                $(this.fieldReplacePowerTargets).addClass('is-hidden');
                $(this.fieldReplaceIrrTargets).addClass('is-hidden');
                $(this.fieldHourTargets).addClass('is-hidden');
                $(this.fieldEnergyValueTargets).addClass('is-hidden');
                $(this.fieldIrrValueTargets).addClass('is-hidden');
                $(this.fieldCorrectionTargets).addClass('is-hidden');
                $(this.fieldEvaluationTargets).addClass('is-hidden');
                $(this.fieldReasonTargets).addClass('is-hidden');
                $(this.fieldAktDep1Targets).addClass('is-hidden');
                $(this.fieldAktDep2Targets).addClass('is-hidden');
                $(this.fieldAktDep3Targets).addClass('is-hidden');
                $(this.inverterDivTargets).addClass('is-hidden');
                $(this.formHourTargets).prop('checked', false);
                body.find('input:checkbox[class=js-checkbox]').each(function () {
                    $(this).prop('checked', true);
                    if (inverterString == '') {
                        inverterString = inverterString + $(this).prop('name');
                        inverterNameString = inverterNameString + $(this).prop('id');
                    } else {
                        inverterString = inverterString + ', ' + $(this).prop('name');
                        inverterNameString = inverterNameString + ', ' + $(this).prop('id');
                    }
                    body.find($('#div-split-'+$(this).prop('name')+'a')).removeClass('is-hidden');
                    body.find($('#split-'+$(this).prop('name')+'a')).prop('checked', true);
                    body.find($('#div-split-'+$(this).prop('name')+'b')).removeClass('is-hidden');
                });

                inverterString = '*';
                body.find('#ticket_form_inverter').val(inverterString);
                body.find('#ticket_form_inverterName').val(inverterNameString);
                $(this.formkpiStatusTargets).removeClass('is-hidden');
                $(this.fieldPRMethodTargets).addClass('is-hidden');
                if (this.formUrlValue === '/ticket/create'){ body.find('#ticket_form_KpiStatus').val(10)};
                break;
            case '71':
                $(this.headerExcludeTargets).addClass('is-hidden');
                $(this.headerReplaceTargets).removeClass('is-hidden');
                $(this.headerReplacePowerTargets).addClass('is-hidden');
                $(this.headerReplaceIrrTargets).addClass('is-hidden');
                $(this.headerHourTargets).addClass('is-hidden');
                $(this.headerEnergyValueTargets).addClass('is-hidden');
                $(this.headerIrrValueTargets).addClass('is-hidden');
                $(this.headerCorrectionTargets).addClass('is-hidden');
                $(this.headerEvaluationTargets).addClass('is-hidden');
                $(this.headerReasonTargets).addClass('is-hidden');
                $(this.headerAktDep1Targets).addClass('is-hidden');
                $(this.headerAktDep2Targets).addClass('is-hidden');
                $(this.headerAktDep3Targets).addClass('is-hidden');
                $(this.headerFormKpiTargets).removeClass('is-hidden');
                $(this.headerPRMethodTargets).addClass('is-hidden');

                $(this.fieldSensorTargets).removeClass('is-hidden');
                $(this.fieldReplacePowerTargets).addClass('is-hidden');
                $(this.fieldReplaceIrrTargets).addClass('is-hidden');
                $(this.fieldHourTargets).addClass('is-hidden');
                $(this.fieldEnergyValueTargets).addClass('is-hidden');
                $(this.fieldIrrValueTargets).addClass('is-hidden');
                $(this.fieldCorrectionTargets).addClass('is-hidden');
                $(this.fieldEvaluationTargets).addClass('is-hidden');
                $(this.fieldReasonTargets).addClass('is-hidden');
                $(this.fieldAktDep1Targets).addClass('is-hidden');
                $(this.fieldAktDep2Targets).addClass('is-hidden');
                $(this.fieldAktDep3Targets).addClass('is-hidden');
                $(this.inverterDivTargets).addClass('is-hidden');
                $(this.formHourTargets).prop('checked', false);
                body.find('input:checkbox[class=js-checkbox]').each(function () {
                    $(this).prop('checked', true);
                    if (inverterString == '') {
                        inverterString = inverterString + $(this).prop('name');
                        inverterNameString = inverterNameString + $(this).prop('id');
                    } else {
                        inverterString = inverterString + ', ' + $(this).prop('name');
                        inverterNameString = inverterNameString + ', ' + $(this).prop('id');
                    }
                    body.find($('#div-split-'+$(this).prop('name')+'a')).removeClass('is-hidden');
                    body.find($('#split-'+$(this).prop('name')+'a')).prop('checked', true);
                    body.find($('#div-split-'+$(this).prop('name')+'b')).removeClass('is-hidden');
                });

                inverterString = '*';
                body.find('#ticket_form_inverter').val(inverterString);
                body.find('#ticket_form_inverterName').val(inverterNameString);
                $(this.formkpiStatusTargets).removeClass('is-hidden');
                $(this.fieldPRMethodTargets).addClass('is-hidden');
                if (this.formUrlValue === '/ticket/create') {body.find('#ticket_form_KpiStatus').val(20)};
                break;
            case '72':
                $(this.headerExcludeTargets).addClass('is-hidden');
                $(this.headerReplaceTargets).addClass('is-hidden');
                $(this.headerReplacePowerTargets).addClass('is-hidden');
                $(this.headerReplaceIrrTargets).addClass('is-hidden');
                $(this.headerHourTargets).removeClass('is-hidden');
                $(this.headerEnergyValueTargets).addClass('is-hidden');
                $(this.headerIrrValueTargets).addClass('is-hidden');
                $(this.headerCorrectionTargets).addClass('is-hidden');
                $(this.headerEvaluationTargets).addClass('is-hidden');
                $(this.headerReasonTargets).addClass('is-hidden');
                $(this.headerAktDep1Targets).addClass('is-hidden');
                $(this.headerAktDep2Targets).addClass('is-hidden');
                $(this.headerAktDep3Targets).addClass('is-hidden');
                $(this.headerFormKpiTargets).removeClass('is-hidden');
                $(this.headerPRMethodTargets).removeClass('is-hidden');


                $(this.fieldSensorTargets).addClass('is-hidden');
                $(this.fieldReplacePowerTargets).addClass('is-hidden');
                $(this.fieldReplaceIrrTargets).addClass('is-hidden');
                $(this.fieldHourTargets).removeClass('is-hidden');
                $(this.fieldEnergyValueTargets).addClass('is-hidden');
                $(this.fieldIrrValueTargets).addClass('is-hidden');
                $(this.fieldCorrectionTargets).addClass('is-hidden');
                $(this.fieldEvaluationTargets).addClass('is-hidden');
                $(this.fieldReasonTargets).addClass('is-hidden');
                $(this.fieldAktDep1Targets).addClass('is-hidden');
                $(this.fieldAktDep2Targets).addClass('is-hidden');
                $(this.fieldAktDep3Targets).addClass('is-hidden');
                $(this.inverterDivTargets).addClass('is-hidden');
                $(this.fieldPRMethodTargets).removeClass('is-hidden');

                if (this.formUrlValue === '/ticket/create') {body.find('#ticket_form_KpiStatus').val(10)};
                break;
            case '73':
                this.replaceCheck();
                break;
            case '74':
                $(this.headerExcludeTargets).addClass('is-hidden');
                $(this.headerReplaceTargets).addClass('is-hidden');
                $(this.headerReplacePowerTargets).addClass('is-hidden');
                $(this.headerReplaceIrrTargets).addClass('is-hidden');
                $(this.headerHourTargets).addClass('is-hidden');
                $(this.headerEnergyValueTargets).addClass('is-hidden');
                $(this.headerIrrValueTargets).addClass('is-hidden');
                $(this.headerCorrectionTargets).removeClass('is-hidden');
                $(this.headerEvaluationTargets).addClass('is-hidden');
                $(this.headerReasonTargets).removeClass('is-hidden');
                $(this.headerAktDep1Targets).addClass('is-hidden');
                $(this.headerAktDep2Targets).addClass('is-hidden');
                $(this.headerAktDep3Targets).addClass('is-hidden');
                $(this.headerFormKpiTargets).removeClass('is-hidden');
                $(this.headerPRMethodTargets).addClass('is-hidden');

                $(this.fieldSensorTargets).addClass('is-hidden');
                $(this.fieldReplacePowerTargets).addClass('is-hidden');
                $(this.fieldReplaceIrrTargets).addClass('is-hidden');
                $(this.fieldHourTargets).addClass('is-hidden');
                $(this.fieldEnergyValueTargets).addClass('is-hidden');
                $(this.fieldIrrValueTargets).addClass('is-hidden');
                $(this.fieldCorrectionTargets).removeClass('is-hidden');
                $(this.fieldEvaluationTargets).addClass('is-hidden');
                $(this.fieldReasonTargets).removeClass('is-hidden');
                $(this.fieldAktDep1Targets).addClass('is-hidden');
                $(this.fieldAktDep2Targets).addClass('is-hidden');
                $(this.fieldAktDep3Targets).addClass('is-hidden');
                $(this.inverterDivTargets).addClass('is-hidden');
                $(this.fieldPRMethodTargets).addClass('is-hidden');

                let reason = $(this.formReasonSelectTarget).val();
                $(this.reasonInputTarget).val(reason);

                if (this.formUrlValue === '/ticket/create') {body.find('#ticket_form_KpiStatus').val(20);}
                break;
            case '100':
                $(this.headerExcludeTargets).addClass('is-hidden');
                $(this.headerReplaceTargets).addClass('is-hidden');
                $(this.headerReplacePowerTargets).addClass('is-hidden');
                $(this.headerReplaceIrrTargets).addClass('is-hidden');
                $(this.headerHourTargets).addClass('is-hidden');
                $(this.headerEnergyValueTargets).addClass('is-hidden');
                $(this.headerIrrValueTargets).addClass('is-hidden');
                $(this.headerCorrectionTargets).addClass('is-hidden');
                $(this.headerEvaluationTargets).removeClass('is-hidden');
                $(this.headerReasonTargets).addClass('is-hidden');
                $(this.headerAktDep1Targets).removeClass('is-hidden');
                $(this.headerAktDep2Targets).removeClass('is-hidden');
                $(this.headerAktDep3Targets).removeClass('is-hidden');
                $(this.headerFormKpiTargets).addClass('is-hidden');
                $(this.headerPRMethodTargets).addClass('is-hidden');

                $(this.fieldSensorTargets).addClass('is-hidden');
                $(this.fieldReplacePowerTargets).addClass('is-hidden');
                $(this.fieldReplaceIrrTargets).addClass('is-hidden');
                $(this.fieldHourTargets).addClass('is-hidden');
                $(this.fieldEnergyValueTargets).addClass('is-hidden');
                $(this.fieldIrrValueTargets).addClass('is-hidden');
                $(this.fieldCorrectionTargets).addClass('is-hidden');
                $(this.fieldEvaluationTargets).removeClass('is-hidden');
                $(this.fieldReasonTargets).addClass('is-hidden');
                $(this.fieldAktDep1Targets).removeClass('is-hidden');
                $(this.fieldAktDep2Targets).removeClass('is-hidden');
                $(this.fieldAktDep3Targets).removeClass('is-hidden');
                $(this.inverterDivTargets).removeClass('is-hidden');

                $(this.formHourTargets).prop('checked', false);
                $(this.formkpiStatusTargets).addClass('is-hidden');
                $(this.fieldPRMethodTargets).addClass('is-hidden');
                if (this.formUrlValue === '/ticket/create'){ body.find('#ticket_form_KpiStatus').val(20)};
                break;
            case '':
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
                $(this.headerAktDep3Targets).addClass('is-hidden');
                $(this.headerAktDep3Targets).addClass('is-hidden');
                $(this.headerFormKpiTargets).addClass('is-hidden');
                $(this.headerPRMethodTargets).addClass('is-hidden');

                $(this.fieldSensorTargets).addClass('is-hidden');
                $(this.fieldReplacePowerTargets).addClass('is-hidden');
                $(this.fieldReplaceIrrTargets).addClass('is-hidden');
                $(this.fieldHourTargets).addClass('is-hidden');
                $(this.fieldEnergyValueTargets).addClass('is-hidden');
                $(this.fieldIrrValueTargets).addClass('is-hidden');
                $(this.fieldCorrectionTargets).addClass('is-hidden');
                $(this.fieldEvaluationTargets).addClass('is-hidden');
                $(this.fieldAktDep1Targets).addClass('is-hidden');
                $(this.fieldAktDep2Targets).addClass('is-hidden');
                $(this.fieldAktDep3Targets).addClass('is-hidden');
                $(this.fieldPRMethodTargets).addClass('is-hidden');
                $(this.inverterDivTargets).removeClass('is-hidden');
                $(this.formHourTarget).prop('checked', false);
                if (this.formUrlValue === '/ticket/create') {body.find('#ticket_form_KpiStatus').val(20);}
                break;
            default:

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
                $(this.headerAktDep2Targets).addClass('is-hidden');
                $(this.headerAktDep3Targets).addClass('is-hidden');
                $(this.headerFormKpiTargets).addClass('is-hidden');
                $(this.headerPRMethodTargets).addClass('is-hidden');

                $(this.fieldSensorTargets).addClass('is-hidden');
                $(this.fieldReplacePowerTargets).addClass('is-hidden');
                $(this.fieldReplaceIrrTargets).addClass('is-hidden');
                $(this.fieldHourTargets).addClass('is-hidden');
                $(this.fieldEnergyValueTargets).addClass('is-hidden');
                $(this.fieldIrrValueTargets).addClass('is-hidden');
                $(this.fieldCorrectionTargets).addClass('is-hidden');
                $(this.fieldEvaluationTargets).addClass('is-hidden');
                $(this.fieldAktDep1Targets).addClass('is-hidden');
                $(this.fieldAktDep2Targets).addClass('is-hidden');
                $(this.fieldAktDep3Targets).addClass('is-hidden');
                $(this.formHourTargets).prop('checked', false);
                $(this.fieldPRMethodTargets).addClass('is-hidden');
                $(this.inverterDivTargets).removeClass('is-hidden');
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
        if ($(this.switchTarget).prop('checked')) {
            body.find('input:checkbox[class=js-checkbox]').each(function () {
                $(this).prop('checked', true);
                if (inverterString == '') {
                    inverterString = inverterString + $(this).prop('name');
                    inverterNameString = inverterNameString + $(this).prop('id');
                } else {
                    inverterString = inverterString + ', ' + $(this).prop('name');
                    inverterNameString = inverterNameString + ', ' + $(this).prop('id');
                }
                body.find($('#div-split-'+$(this).prop('name')+'a')).removeClass('is-hidden');
                body.find($('#split-'+$(this).prop('name')+'a')).prop('checked', true);
                body.find($('#div-split-'+$(this).prop('name')+'b')).removeClass('is-hidden');
            });
            if (edited == true) {
                $(this.splitDeployTarget).removeAttr('disabled');
            }
            inverterString = '*';
            inverterNameString = '*';
        } else {
            $(this.modalBodyTarget).find('input:checkbox[class=js-checkbox]').each(function(){
                $(this).prop('checked', false);
            });
            $(this.splitDeployTarget).attr('disabled', 'disabled');
            inverterString = '';
            inverterNameString = '*';
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
        if ($(this.formBeginTarget).prop('value') != this.formBeginTarget.dataset.originalValue) {
            const valueBegin = $(this.formBeginTarget).prop('value');
            const date = new Date(valueBegin);
            date.setSeconds(0);
            const timestamp1 = date.getTime();
            if (timestamp1 % 300000 == 0) {
                var hour = date.getHours();
                var minutes = date.getMinutes();
                /*
                switch (minutes) {
                    case 0:
                    case 5:
                        minutes = 15;
                        break;
                    case 10:
                    case 15:
                    case 20:
                        minutes = 30;
                        break;
                    case 25:
                    case 30:
                    case 35:
                        minutes = 45;
                        break;
                    case 40:
                    case 45:
                    case 50:
                        hour = hour + 1;
                        minutes = 0;
                        break;
                    case 55:
                        hour = hour + 1;
                        minutes = 15;
                        break;
                }
                */
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
                $(this.formBeginTarget).val(newStringdate);
                $(this.formBeginDateTarget).val(newStringdate);
            }
        }


       this.saveCheck();
    }
    endCheck()
        {
            if ($(this.formEndTarget).prop('value') != this.formEndTarget.dataset.originalValue) {
                const valueBegin = $(this.formEndTarget).prop('value');
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
                    $(this.formEndTarget).val(newStringdate);
                    $(this.formEndDateTarget).val(newStringdate);
                }
            }

        this.saveCheck();
    }
    saveCheck(){
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
                inverterString = inverterString + $(this).prop('name');
                inverterNameString = inverterNameString + $(this).prop('id');
            }
            else {
                inverterString = inverterString + ', ' + $(this).prop('name');
                inverterNameString = inverterNameString + ', ' + $(this).prop('id');
            }
            body.find($('#div-split-'+$(this).prop('name')+'a')).removeClass('is-hidden');
            body.find($('#div-split-'+$(this).prop('name')+'b')).removeClass('is-hidden');
            body.find($('#split-'+$(this).prop('name')+'a')).prop('checked', true);

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