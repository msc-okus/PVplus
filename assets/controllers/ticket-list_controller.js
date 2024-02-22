import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';
import {Reveal} from "foundation-sites";
import {useDispatch} from "stimulus-use";

export default class extends Controller {
    static targets = ['list', 'searchBar', 'modalCreate', 'modalCreateBody', 'AlertFormat', 'AlertDates', 'saveButton', 'formBegin', 'formEnd', 'sort', 'direction', 'proofam', 'proofepc', 'prooftam', 'proofg4n', 'ignored', 'proofmaintenance', 'selectedInverter', 'anlageselect', 'InverterSearchDropdown', 'InverterSearchButton', 'switch'];
    static values = {
        urlCreate: String,
        urlSearch: String,
    }

    connect() {
        useDispatch(this);
        this.disableAllToolTips()
    }

    async search(event) {
        event.preventDefault();
        const $searchListform = $(this.searchBarTarget).find('form');
        var serializedData = $searchListform.serialize().concat("&page=1");
        const $queryParams = $(event.currentTarget).data("query-value");
        this.listTarget.innerHTML = await $.ajax({
            url: this.urlSearchValue,
            method: $searchListform.prop('method'),
            data: serializedData,
        });
        $(document).foundation();
        this.disableAllToolTips()
    }
    async update(event) {
        event.preventDefault();
        const $searchListform = $(this.searchBarTarget).find('form');
        var serializedData = $searchListform.serialize();
        this.listTarget.innerHTML = await $.ajax({
            url: this.urlSearchValue,
            method: $searchListform.prop('method'),
            data: serializedData,
        });
        var response = await $.ajax({
            url: '/ticket/proofCount',
        });
        this.proofepcTarget.innerText = response['countProofByEPC'];
        this.prooftamTarget.innerText = response['countProofByTAM'];
        this.proofg4nTarget.innerText = response['countProofByG4N'];
        this.proofamTarget.innerText = response['countProofByAM'];
        this.ignoredTarget.innerText = response['countIgnored'];
        this.proofmaintenanceTarget.innerText = response['countProofByMaintenance'];

        $(document).foundation();
        this.disableAllToolTips()
    }

    async page(event) {
        event.preventDefault();
        const $queryParams = $(event.currentTarget).data("query-value");
        this.listTarget.innerHTML = await $.ajax({
            url: this.urlSearchValue,
            data: $queryParams,
        });
        $(document).foundation();
        this.disableAllToolTips()
    }
    async sortId(event) {
        event.preventDefault();
        $(this.sortTarget).val('ticket.id');

        if ($(this.directionTarget).val() === '') {$(this.directionTarget).val('ASC');}
        else if ($(this.directionTarget).val() === 'ASC'){$(this.directionTarget).val('DESC');}
        else {$(this.directionTarget).val('ASC');}
        this.listTarget.innerHTML = await $.ajax({
            url: event.currentTarget.href,

        });
        $(document).foundation();
        this.disableAllToolTips()
    }

    async sortBegin(event) {
        $(this.sortTarget).val('ticket.begin');
        if ($(this.directionTarget).val() === '') {$(this.directionTarget).val('ASC');}
        else if ($(this.directionTarget).val() === 'ASC'){$(this.directionTarget).val('DESC');}
        else {$(this.directionTarget).val('ASC');}
        event.preventDefault();
        this.listTarget.innerHTML = await $.ajax({
            url: event.currentTarget.href,

                    });
        $(document).foundation();
        this.disableAllToolTips()
    }
    async sortEnd(event) {
        event.preventDefault();
        $(this.sortTarget).val('ticket.end');
        if ($(this.directionTarget).val() === '') {$(this.directionTarget).val('ASC');}
        else if ($(this.directionTarget).val() === 'ASC'){$(this.directionTarget).val('DESC');}
        else {$(this.directionTarget).val('ASC');}
        this.listTarget.innerHTML = await $.ajax({
            url: event.currentTarget.href,

        });
        $(document).foundation();
    }
    async sortUpdate(event) {
        event.preventDefault();
        $(this.sortTarget).val('ticket.updatedAt');
        if ($(this.directionTarget).val() === '') {$(this.directionTarget).val('ASC');}
        else if ($(this.directionTarget).val() === 'ASC'){$(this.directionTarget).val('DESC');}
        else {$(this.directionTarget).val('ASC');}
        this.listTarget.innerHTML = await $.ajax({
            url: event.currentTarget.href,

        });
        $(document).foundation();
        this.disableAllToolTips()
    }

    disableAllToolTips(){
        $("[id*='tooltip']").each(function() {
            $(this).css('display', 'none');
        });
    }
    async selectAnlage(){
        let id= $(this.anlageselectTarget).val();
        if (id !=  '') {
            console.log(  $(this.InverterSearchDropdownTarget));
            $(this.InverterSearchButtonTarget).removeAttr('disabled');
                this.InverterSearchDropdownTarget.innerHTML = await $.ajax({
                url: '/list/getinverterarray/' + id,
            });
        }
        else{
            $(this.InverterSearchButtonTarget).attr('disabled', 'disabled');
        }
    }
    checkTrafo({ params: { first, last, trafo }}){
        let body = $(this.InverterSearchDropdownTarget);
        let checked = $("#trafo" + trafo).prop('checked');
        body.find('input:checkbox[class=js-checkbox]').each(function (){
            console.log($(this).prop('id').substring(2), first, last);
            if ($(this).prop('id').substring(2) >= first) {
                if ($(this).prop('id').substring(2) <= last){
                    if (checked) $(this).prop('checked', true);
                    else $(this).prop('checked', false);
                }
            }
        });
        $(this.switchTarget).prop('checked', false)
        this.setInverter()
    }
    setInverter(){
        let inverterString = '';
        let body = $(this.InverterSearchDropdownTarget);
        let counter = 0;
        body.find('input:checkbox[class=js-checkbox]:checked').each(function (){
            counter ++;
            if (inverterString == '') {
                inverterString = inverterString + $(this).prop('id').substring(2);
            }
            else {
                inverterString = inverterString + ', ' + $(this).prop('id').substring(2);

            }
        });
        console.log(inverterString);
        if (counter == body.find('input:checkbox[class=js-checkbox]').length){
            inverterString = '*';
        }
        $('#inverterSearch').val(inverterString);
    }
    selectAll(){
        let inverterString = '';
        let body = $(this.InverterSearchDropdownTarget);
        if ($(this.switchTarget).prop('checked')) {
            body.find('input:checkbox[class=js-checkbox-trafo]').each(function () {
                $(this).prop('checked', true);
            });
            body.find('input:checkbox[class=js-checkbox]').each(function () {
                $(this).prop('checked', true);
            });
            inverterString = '*';
        } else {
            body.find('input:checkbox[class=js-checkbox-trafo]').each(function () {
                $(this).prop('checked', false);
            });
            body.find('input:checkbox[class=js-checkbox]').each(function(){
                $(this).prop('checked', false);
            });

            inverterString = '';

        }
        $('#inverterSearch').val(inverterString);
    }
}