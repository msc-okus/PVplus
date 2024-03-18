import { Controller } from '@hotwired/stimulus';
import { useDispatch } from 'stimulus-use';
import { Reveal } from 'foundation-sites';
import $ from 'jquery';

export default class extends Controller {
    static targets =    ['splitAlert', 'modal', 'modalBody', 'splitModal', 'splitForm', 'switch', 'deactivable',
        'anlage', 'saveButton',
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
    connect() {
        useDispatch(this);
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
        let invids = $('#invids');
        let invnames = $("#invnames");

        let chekedInverters = '';
        let chekedInverterIds = '';
        let temp= '';
        body.find('input:checkbox[class=js-checkbox]').each(function () {
            if($(this).prop('checked')){
                chekedInverters = chekedInverters+$(this).attr('name')+',';
                temp = $(this).attr('name').replaceAll(' ','')
                temp = temp.replaceAll('.','_')
                chekedInverterIds = chekedInverterIds+$("#"+temp).val()+',';
            }
        });
        chekedInverters = chekedInverters.slice(0, -1);
        chekedInverterIds = chekedInverterIds.slice(0, -1);

        invnames.prop('value', chekedInverters);
        invids.prop('value', chekedInverterIds);

        $("#mysubmit").val('yes');
        $("#chart-control").delay(100).submit()
    }
}
