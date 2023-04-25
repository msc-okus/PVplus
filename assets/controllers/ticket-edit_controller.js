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
                        'headerPRMethod', 'fieldPRMethod', 'scope', 'reasonInput'];
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
        this.checkCategory();

        $(this.modalBodyTarget).foundation();
    }

    reasonCheck(){
        let reason = $(this.reasonInputTarget).val();
        $(this.formReasonSelectTarget).val(reason);
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
            console.log($(this.formBeginTarget).prop('value'), $(this.formEndTarget).prop('value'));
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
            if (endDate.getMinutes() > 0) hour = endDate.getHours() + 1;
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

            let newStringEndDate = endDate.getFullYear().toString().concat('-', endMonth, '-', endDay, 'T', endHour, ':', '00');

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

            $(this.headerExcludeTarget).addClass('is-hidden');
            $(this.headerReplaceTarget).addClass('is-hidden');
            $(this.headerReplacePowerTarget).removeClass('is-hidden');
            if ($(this.formReplaceTarget).prop('checked') == false) {
                $(this.headerReplaceIrrTarget).addClass('is-hidden');
                $(this.headerEnergyValueTarget).removeClass('is-hidden');
                $(this.headerIrrValueTarget).removeClass('is-hidden');
            }
            else{
                $(this.headerReplaceIrrTarget).removeClass('is-hidden');
                //$(this.headerHourTarget).addClass('is-hidden');
                $(this.headerEnergyValueTarget).addClass('is-hidden');
                $(this.headerIrrValueTarget).addClass('is-hidden');
            }
            $(this.headerHourTarget).removeClass('is-hidden');
            $(this.headerCorrectionTarget).addClass('is-hidden');
            $(this.headerEvaluationTarget).addClass('is-hidden');
            $(this.headerReasonTarget).removeClass('is-hidden');
            $(this.headerAktDep1Target).addClass('is-hidden');
            $(this.headerAktDep2Target).addClass('is-hidden');
            $(this.headerAktDep3Target).addClass('is-hidden');
            $(this.headerFormKpiTarget).removeClass('is-hidden');
            $(this.headerPRMethodTarget).addClass('is-hidden');

            $(this.fieldSensorTarget).addClass('is-hidden');
            $(this.fieldReplacePowerTarget).removeClass('is-hidden');
            if ($(this.formReplaceTarget).prop('checked') == false) {
                $(this.fieldReplaceIrrTarget).addClass('is-hidden');
                $(this.fieldEnergyValueTarget).removeClass('is-hidden');
                $(this.fieldIrrValueTarget).removeClass('is-hidden');
            }
            else{
                $(this.fieldReplaceIrrTarget).removeClass('is-hidden');
                //$(this.fieldHourTarget).addClass('is-hidden');
                $(this.fieldEnergyValueTarget).addClass('is-hidden');
                $(this.fieldIrrValueTarget).addClass('is-hidden');
            }
            console.log("hi");
            $(this.fieldHourTarget).removeClass('is-hidden');
            $(this.fieldCorrectionTarget).addClass('is-hidden');
            $(this.fieldEvaluationTarget).addClass('is-hidden');
            $(this.fieldReasonTarget).removeClass('is-hidden');
            $(this.fieldAktDep1Target).addClass('is-hidden');
            $(this.fieldAktDep2Target).addClass('is-hidden');
            $(this.fieldAktDep3Target).addClass('is-hidden');
            $(this.formkpiStatusTarget).removeClass('is-hidden');
            $(this.fieldPRMethodTarget).addClass('is-hidden');
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
        let body = $(this.modalBodyTarget);
        // in this switch we remove the hidding class to show the fields of the ticket date on demand

        if (cat >= 70 && cat <= 80 ){
            $(this.scopeTarget).removeClass('is-hidden');
            body.find('input:checkbox[class=js-checkbox]').each(function () {
                $(this).prop('checked', true);
                if (inverterString == '')
                {
                    inverterString = inverterString + $(this).prop('name');
                }
                else
                {
                    inverterString = inverterString + ', ' + $(this).prop('name');
                }
                body.find($('#div-split-'+$(this).prop('name')+'a')).removeClass('is-hidden');
                body.find($('#split-'+$(this).prop('name')+'a')).prop('checked', true);
                body.find($('#div-split-'+$(this).prop('name')+'b')).removeClass('is-hidden');
            });

            inverterString = '*';
            body.find('#ticket_form_inverter').val(inverterString);
        }
        else{
            $(this.scopeTarget).addClass('is-hidden');
        }
        switch (cat){
            case '10':
                $(this.headerExcludeTarget).addClass('is-hidden');
                $(this.headerReplaceTarget).addClass('is-hidden');
                $(this.headerReplacePowerTarget).addClass('is-hidden');
                $(this.headerReplaceIrrTarget).addClass('is-hidden');
                $(this.headerHourTarget).addClass('is-hidden');
                $(this.headerEnergyValueTarget).addClass('is-hidden');
                $(this.headerIrrValueTarget).addClass('is-hidden');
                $(this.headerCorrectionTarget).addClass('is-hidden');
                $(this.headerEvaluationTarget).removeClass('is-hidden');
                $(this.headerReasonTarget).addClass('is-hidden');
                $(this.headerAktDep1Target).removeClass('is-hidden');
                $(this.headerAktDep2Target).removeClass('is-hidden');
                $(this.headerAktDep3Target).removeClass('is-hidden');

                $(this.headerFormKpiTarget).addClass('is-hidden');
                $(this.headerPRMethodTarget).addClass('is-hidden');


                $(this.fieldSensorTarget).addClass('is-hidden');
                $(this.fieldReplacePowerTarget).addClass('is-hidden');
                $(this.fieldReplaceIrrTarget).addClass('is-hidden');
                $(this.fieldHourTarget).addClass('is-hidden');
                $(this.fieldEnergyValueTarget).addClass('is-hidden');
                $(this.fieldIrrValueTarget).addClass('is-hidden');
                $(this.fieldCorrectionTarget).addClass('is-hidden');
                $(this.fieldReasonTarget).addClass('is-hidden');
                $(this.fieldEvaluationTarget).removeClass('is-hidden');
                $(this.fieldAktDep1Target).removeClass('is-hidden');
                $(this.fieldAktDep2Target).removeClass('is-hidden');
                $(this.fieldAktDep3Target).removeClass('is-hidden');
                $(this.inverterDivTarget).removeClass('is-hidden');
                $(this.formHourTarget).prop('checked', false);
                $(this.formkpiStatusTarget).addClass('is-hidden');
                $(this.fieldPRMethodTarget).addClass('is-hidden');
                if (this.formUrlValue === '/ticket/create'){ body.find('#ticket_form_KpiStatus').val(20)};
                break;
            case '20':
                console.log('entro');

                $(this.headerExcludeTarget).addClass('is-hidden');
                $(this.headerReplaceTarget).addClass('is-hidden');
                $(this.headerReplacePowerTarget).addClass('is-hidden');
                $(this.headerReplaceIrrTarget).addClass('is-hidden');
                $(this.headerHourTarget).addClass('is-hidden');
                $(this.headerEnergyValueTarget).addClass('is-hidden');
                $(this.headerIrrValueTarget).addClass('is-hidden');
                $(this.headerCorrectionTarget).addClass('is-hidden');
                $(this.headerEvaluationTarget).removeClass('is-hidden');
                $(this.headerReasonTarget).addClass('is-hidden');
                $(this.headerAktDep1Target).removeClass('is-hidden');
                $(this.headerAktDep2Target).removeClass('is-hidden');
                $(this.headerAktDep3Target).removeClass('is-hidden');
                $(this.headerFormKpiTarget).addClass('is-hidden');
                $(this.headerPRMethodTarget).addClass('is-hidden');


                $(this.fieldSensorTarget).addClass('is-hidden');
                $(this.fieldReplacePowerTarget).addClass('is-hidden');
                $(this.fieldReplaceIrrTarget).addClass('is-hidden');
                $(this.fieldHourTarget).addClass('is-hidden');
                $(this.fieldEnergyValueTarget).addClass('is-hidden');
                $(this.fieldIrrValueTarget).addClass('is-hidden');
                $(this.fieldCorrectionTarget).addClass('is-hidden');
                $(this.fieldEvaluationTarget).removeClass('is-hidden');
                $(this.fieldReasonTarget).addClass('is-hidden');
                $(this.fieldAktDep1Target).removeClass('is-hidden');
                $(this.fieldAktDep2Target).removeClass('is-hidden');
                $(this.fieldAktDep3Target).removeClass('is-hidden');
                $(this.inverterDivTarget).removeClass('is-hidden');

                $(this.formHourTarget).prop('checked', false);
                $(this.formkpiStatusTarget).addClass('is-hidden');
                $(this.fieldPRMethodTarget).addClass('is-hidden');
                if (this.formUrlValue === '/ticket/create'){ body.find('#ticket_form_KpiStatus').val(20)};
                break;
            case '70':
                $(this.headerExcludeTarget).removeClass('is-hidden');
                $(this.headerReplaceTarget).addClass('is-hidden');
                $(this.headerReplacePowerTarget).addClass('is-hidden');
                $(this.headerReplaceIrrTarget).addClass('is-hidden');
                $(this.headerHourTarget).addClass('is-hidden');
                $(this.headerEnergyValueTarget).addClass('is-hidden');
                $(this.headerIrrValueTarget).addClass('is-hidden');
                $(this.headerCorrectionTarget).addClass('is-hidden');
                $(this.headerEvaluationTarget).addClass('is-hidden');
                $(this.headerReasonTarget).addClass('is-hidden');
                $(this.headerAktDep1Target).addClass('is-hidden');
                $(this.headerAktDep2Target).addClass('is-hidden');
                $(this.headerAktDep3Target).addClass('is-hidden');
                $(this.headerFormKpiTarget).removeClass('is-hidden');
                $(this.headerPRMethodTarget).addClass('is-hidden');

                $(this.fieldSensorTarget).removeClass('is-hidden');
                $(this.fieldReplacePowerTarget).addClass('is-hidden');
                $(this.fieldReplaceIrrTarget).addClass('is-hidden');
                $(this.fieldHourTarget).addClass('is-hidden');
                $(this.fieldEnergyValueTarget).addClass('is-hidden');
                $(this.fieldIrrValueTarget).addClass('is-hidden');
                $(this.fieldCorrectionTarget).addClass('is-hidden');
                $(this.fieldEvaluationTarget).addClass('is-hidden');
                $(this.fieldReasonTarget).addClass('is-hidden');
                $(this.fieldAktDep1Target).addClass('is-hidden');
                $(this.fieldAktDep2Target).addClass('is-hidden');
                $(this.fieldAktDep3Target).addClass('is-hidden');
                $(this.inverterDivTarget).addClass('is-hidden');
                $(this.formHourTarget).prop('checked', false);
                body.find('input:checkbox[class=js-checkbox]').each(function () {
                    $(this).prop('checked', true);
                    if (inverterString == '')
                    {
                        inverterString = inverterString + $(this).prop('name');
                    }
                    else
                    {
                        inverterString = inverterString + ', ' + $(this).prop('name');
                    }
                    body.find($('#div-split-'+$(this).prop('name')+'a')).removeClass('is-hidden');
                    body.find($('#split-'+$(this).prop('name')+'a')).prop('checked', true);
                    body.find($('#div-split-'+$(this).prop('name')+'b')).removeClass('is-hidden');
                });

                inverterString = '*';
                body.find('#ticket_form_inverter').val(inverterString);
                $(this.formkpiStatusTarget).removeClass('is-hidden');
                $(this.fieldPRMethodTarget).addClass('is-hidden');
                if (this.formUrlValue === '/ticket/create'){ body.find('#ticket_form_KpiStatus').val(20)};
                break;
            case '71':
                $(this.headerExcludeTarget).addClass('is-hidden');
                $(this.headerReplaceTarget).removeClass('is-hidden');
                $(this.headerReplacePowerTarget).addClass('is-hidden');
                $(this.headerReplaceIrrTarget).addClass('is-hidden');
                $(this.headerHourTarget).addClass('is-hidden');
                $(this.headerEnergyValueTarget).addClass('is-hidden');
                $(this.headerIrrValueTarget).addClass('is-hidden');
                $(this.headerCorrectionTarget).addClass('is-hidden');
                $(this.headerEvaluationTarget).addClass('is-hidden');
                $(this.headerReasonTarget).addClass('is-hidden');
                $(this.headerAktDep1Target).addClass('is-hidden');
                $(this.headerAktDep2Target).addClass('is-hidden');
                $(this.headerAktDep3Target).addClass('is-hidden');
                $(this.headerFormKpiTarget).removeClass('is-hidden');
                $(this.headerPRMethodTarget).addClass('is-hidden');

                $(this.fieldSensorTarget).removeClass('is-hidden');
                $(this.fieldReplacePowerTarget).addClass('is-hidden');
                $(this.fieldReplaceIrrTarget).addClass('is-hidden');
                $(this.fieldHourTarget).addClass('is-hidden');
                $(this.fieldEnergyValueTarget).addClass('is-hidden');
                $(this.fieldIrrValueTarget).addClass('is-hidden');
                $(this.fieldCorrectionTarget).addClass('is-hidden');
                $(this.fieldEvaluationTarget).addClass('is-hidden');
                $(this.fieldReasonTarget).addClass('is-hidden');
                $(this.fieldAktDep1Target).addClass('is-hidden');
                $(this.fieldAktDep2Target).addClass('is-hidden');
                $(this.fieldAktDep3Target).addClass('is-hidden');
                $(this.inverterDivTarget).addClass('is-hidden');
                $(this.formHourTarget).prop('checked', false);
                body.find('input:checkbox[class=js-checkbox]').each(function () {
                    $(this).prop('checked', true);
                    if (inverterString == '')
                    {
                        inverterString = inverterString + $(this).prop('name');
                    }
                    else
                    {
                        inverterString = inverterString + ', ' + $(this).prop('name');
                    }
                    body.find($('#div-split-'+$(this).prop('name')+'a')).removeClass('is-hidden');
                    body.find($('#split-'+$(this).prop('name')+'a')).prop('checked', true);
                    body.find($('#div-split-'+$(this).prop('name')+'b')).removeClass('is-hidden');
                });

                inverterString = '*';
                body.find('#ticket_form_inverter').val(inverterString);
                $(this.formkpiStatusTarget).removeClass('is-hidden');
                $(this.fieldPRMethodTarget).addClass('is-hidden');
                if (this.formUrlValue === '/ticket/create') {body.find('#ticket_form_KpiStatus').val(20)};
                break;
            case '72':
                $(this.headerExcludeTarget).addClass('is-hidden');
                $(this.headerReplaceTarget).addClass('is-hidden');
                $(this.headerReplacePowerTarget).addClass('is-hidden');
                $(this.headerReplaceIrrTarget).addClass('is-hidden');
                $(this.headerHourTarget).removeClass('is-hidden');
                $(this.headerEnergyValueTarget).addClass('is-hidden');
                $(this.headerIrrValueTarget).addClass('is-hidden');
                $(this.headerCorrectionTarget).addClass('is-hidden');
                $(this.headerEvaluationTarget).addClass('is-hidden');
                $(this.headerReasonTarget).addClass('is-hidden');
                $(this.headerAktDep1Target).addClass('is-hidden');
                $(this.headerAktDep2Target).addClass('is-hidden');
                $(this.headerAktDep3Target).addClass('is-hidden');
                $(this.headerFormKpiTarget).removeClass('is-hidden');
                $(this.headerPRMethodTarget).removeClass('is-hidden');


                $(this.fieldSensorTarget).addClass('is-hidden');
                $(this.fieldReplacePowerTarget).addClass('is-hidden');
                $(this.fieldReplaceIrrTarget).addClass('is-hidden');
                $(this.fieldHourTarget).removeClass('is-hidden');
                $(this.fieldEnergyValueTarget).addClass('is-hidden');
                $(this.fieldIrrValueTarget).addClass('is-hidden');
                $(this.fieldCorrectionTarget).addClass('is-hidden');
                $(this.fieldEvaluationTarget).addClass('is-hidden');
                $(this.fieldReasonTarget).addClass('is-hidden');
                $(this.fieldAktDep1Target).addClass('is-hidden');
                $(this.fieldAktDep2Target).addClass('is-hidden');
                $(this.fieldAktDep3Target).addClass('is-hidden');
                $(this.inverterDivTarget).addClass('is-hidden');
                $(this.fieldPRMethodTarget).removeClass('is-hidden');

                if (this.formUrlValue === '/ticket/create') {body.find('#ticket_form_KpiStatus').val(10)};
                break;
            case '73':
                this.replaceCheck();
                break;
            case '74':
                $(this.headerExcludeTarget).addClass('is-hidden');
                $(this.headerReplaceTarget).addClass('is-hidden');
                $(this.headerReplacePowerTarget).addClass('is-hidden');
                $(this.headerReplaceIrrTarget).addClass('is-hidden');
                $(this.headerHourTarget).addClass('is-hidden');
                $(this.headerEnergyValueTarget).addClass('is-hidden');
                $(this.headerIrrValueTarget).addClass('is-hidden');
                $(this.headerCorrectionTarget).removeClass('is-hidden');
                $(this.headerEvaluationTarget).addClass('is-hidden');
                $(this.headerReasonTarget).removeClass('is-hidden');
                $(this.headerAktDep1Target).addClass('is-hidden');
                $(this.headerAktDep2Target).addClass('is-hidden');
                $(this.headerAktDep3Target).addClass('is-hidden');
                $(this.headerFormKpiTarget).removeClass('is-hidden');
                $(this.headerPRMethodTarget).addClass('is-hidden');

                $(this.fieldSensorTarget).addClass('is-hidden');
                $(this.fieldReplacePowerTarget).addClass('is-hidden');
                $(this.fieldReplaceIrrTarget).addClass('is-hidden');
                $(this.fieldHourTarget).addClass('is-hidden');
                $(this.fieldEnergyValueTarget).addClass('is-hidden');
                $(this.fieldIrrValueTarget).addClass('is-hidden');
                $(this.fieldCorrectionTarget).removeClass('is-hidden');
                $(this.fieldEvaluationTarget).addClass('is-hidden');
                $(this.fieldReasonTarget).removeClass('is-hidden');
                $(this.fieldAktDep1Target).addClass('is-hidden');
                $(this.fieldAktDep2Target).addClass('is-hidden');
                $(this.fieldAktDep3Target).addClass('is-hidden');
                $(this.inverterDivTarget).addClass('is-hidden');
                $(this.fieldPRMethodTarget).addClass('is-hidden');

                let reason = $(this.formReasonSelectTarget).val();
                $(this.reasonInputTarget).val(reason);

                if (this.formUrlValue === '/ticket/create') {body.find('#ticket_form_KpiStatus').val(20);}
                break;
            case '':
                $(this.headerExcludeTarget).addClass('is-hidden');
                $(this.headerReplaceTarget).addClass('is-hidden');
                $(this.headerReplacePowerTarget).addClass('is-hidden');
                $(this.headerReplaceIrrTarget).addClass('is-hidden');
                $(this.headerHourTarget).addClass('is-hidden');
                $(this.headerEnergyValueTarget).addClass('is-hidden');
                $(this.headerIrrValueTarget).addClass('is-hidden');
                $(this.headerCorrectionTarget).addClass('is-hidden');
                $(this.headerEvaluationTarget).addClass('is-hidden');
                $(this.headerAktDep1Target).addClass('is-hidden');
                $(this.headerAktDep3Target).addClass('is-hidden');
                $(this.headerAktDep3Target).addClass('is-hidden');
                $(this.headerFormKpiTarget).addClass('is-hidden');
                $(this.headerPRMethodTarget).addClass('is-hidden');

                $(this.fieldSensorTarget).addClass('is-hidden');
                $(this.fieldReplacePowerTarget).addClass('is-hidden');
                $(this.fieldReplaceIrrTarget).addClass('is-hidden');
                $(this.fieldHourTarget).addClass('is-hidden');
                $(this.fieldEnergyValueTarget).addClass('is-hidden');
                $(this.fieldIrrValueTarget).addClass('is-hidden');
                $(this.fieldCorrectionTarget).addClass('is-hidden');
                $(this.fieldEvaluationTarget).addClass('is-hidden');
                $(this.fieldAktDep1Target).addClass('is-hidden');
                $(this.fieldAktDep2Target).addClass('is-hidden');
                $(this.fieldAktDep3Target).addClass('is-hidden');
                $(this.fieldPRMethodTarget).addClass('is-hidden');
                $(this.inverterDivTarget).removeClass('is-hidden');
                $(this.formHourTarget).prop('checked', false);
                if (this.formUrlValue === '/ticket/create') {body.find('#ticket_form_KpiStatus').val(20);}
                break;
            default:

                $(this.headerExcludeTarget).addClass('is-hidden');
                $(this.headerReplaceTarget).addClass('is-hidden');
                $(this.headerReplacePowerTarget).addClass('is-hidden');
                $(this.headerReplaceIrrTarget).addClass('is-hidden');
                $(this.headerHourTarget).addClass('is-hidden');
                $(this.headerEnergyValueTarget).addClass('is-hidden');
                $(this.headerIrrValueTarget).addClass('is-hidden');
                $(this.headerCorrectionTarget).addClass('is-hidden');
                $(this.headerEvaluationTarget).addClass('is-hidden');
                $(this.headerAktDep1Target).addClass('is-hidden');
                $(this.headerAktDep2Target).addClass('is-hidden');
                $(this.headerAktDep3Target).addClass('is-hidden');
                $(this.headerFormKpiTarget).addClass('is-hidden');
                $(this.headerPRMethodTarget).addClass('is-hidden');

                $(this.fieldSensorTarget).addClass('is-hidden');
                $(this.fieldReplacePowerTarget).addClass('is-hidden');
                $(this.fieldReplaceIrrTarget).addClass('is-hidden');
                $(this.fieldHourTarget).addClass('is-hidden');
                $(this.fieldEnergyValueTarget).addClass('is-hidden');
                $(this.fieldIrrValueTarget).addClass('is-hidden');
                $(this.fieldCorrectionTarget).addClass('is-hidden');
                $(this.fieldEvaluationTarget).addClass('is-hidden');
                $(this.fieldAktDep1Target).addClass('is-hidden');
                $(this.fieldAktDep2Target).addClass('is-hidden');
                $(this.fieldAktDep3Target).addClass('is-hidden');
                $(this.formHourTarget).prop('checked', false);
                $(this.fieldPRMethodTarget).addClass('is-hidden');
                $(this.inverterDivTarget).removeClass('is-hidden');
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

        if ($(this.switchTarget).prop('checked')) {
            body.find('input:checkbox[class=js-checkbox]').each(function () {
                $(this).prop('checked', true);
                if (inverterString == '')
                {
                    inverterString = inverterString + $(this).prop('name');
                }
                else
                {
                    inverterString = inverterString + ', ' + $(this).prop('name');
                }
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
            inverterString = '';
        }
        $(this.modalBodyTarget).find('#ticket_form_inverter').val(inverterString);

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

    saveCheck({ params: { edited }}){
        //getting a string with the inverters so later we can check if there is any or none

        let inverterString = '';
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
            if (inverterString == '') {inverterString = inverterString + $(this).prop('name');}
            else {inverterString = inverterString + ', ' + $(this).prop('name');}
            body.find($('#div-split-'+$(this).prop('name')+'a')).removeClass('is-hidden');
            body.find($('#div-split-'+$(this).prop('name')+'b')).removeClass('is-hidden');
            body.find($('#split-'+$(this).prop('name')+'a')).prop('checked', true);

        });
        if (counter == body.find('input:checkbox[class=js-checkbox]').length){
            inverterString = '*';
        }

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
                if ((timestamp1 % 900000 == 0) && (timestamp2 % 900000 == 0)){
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
                if ((timestamp1 % 900000 == 0) && (timestamp2 % 900000 == 0)){
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

                    if ((timestamp1 % 900000 == 0) && (timestamp2 % 900000 == 0)){
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