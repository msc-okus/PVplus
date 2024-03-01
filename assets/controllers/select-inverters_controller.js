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
        'formEndDate', 'formReasonSelect', 'formReasonText', 'headerReason', 'fieldReason', 'formkpiStatus', 'headerFormKpi',
        'headerPRMethod', 'fieldPRMethod', 'scope', 'reasonInput', 'sensorDiv', 'contactModal', 'modalContactBody', 'contactButton', 'modalContactCreateBody',
        'contactModalCreate', 'modalTimelineBody', 'timelineModal'];

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

    checkTrafo({ params: { first, last, trafo }}){
        let body = $('#inverters');
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

    checkSelect(){
        let body = $('#inverters');

        $(this.switchTarget).prop('checked', false)

    }



    selectAll(){
        let body = $('#inverters');

        if ($(this.switchTarget).prop('checked')) {

            body.find('input:checkbox[class=js-checkbox-trafo]').each(function () {
                $(this).prop('checked', true);
            });
            body.find('input:checkbox[class=js-checkbox]').each(function () {
                $(this).prop('checked', true);
            });

            inverterString = '*';
            inverterNameString = '*';
        } else {
            body.find('input:checkbox[class=js-checkbox-trafo]').each(function () {
                $(this).prop('checked', false);
            });
            body.find('input:checkbox[class=js-checkbox]').each(function () {
                $(this).prop('checked', false);
            });
        }

    }

    saveInverters(){

        let body = $('#inverters');
        let target = $('#sviewhidden');
        let chekedInverters = '';
        body.find('input:checkbox[class=js-checkbox]').each(function () {

            if($(this).prop('checked')){
                chekedInverters = chekedInverters+','+$(this).attr('name');
            }

        });
        chekedInverters = chekedInverters.slice(0, -1);
        target.prop('value', chekedInverters);

    }
}