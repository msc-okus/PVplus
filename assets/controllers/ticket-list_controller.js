import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';
import {Reveal} from "foundation-sites";
import {useDispatch} from "stimulus-use";

export default class extends Controller {
    static targets = ['list', 'searchBar', 'modalCreate', 'modalCreateBody', 'AlertFormat', 'AlertDates', 'saveButton',
        'formBegin', 'formEnd', 'sort', 'direction', 'proofam', 'proofepc', 'prooftam',
        'proofg4n', 'ignored', 'proofmaintenance', 'selectedInverter', 'anlageselect',
        'InverterSearchDropdown', 'InverterSearchButton', 'switch', 'deactivableTarget'];
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
        let serializedData = $searchListform.serialize().concat("&page=1");
        let newPlantId = $(this.listTarget).find('#newPlantSelect').find(":selected").val()
        let url = this.urlSearchValue.concat('&newPlantId=', newPlantId);
        this.listTarget.innerHTML = await $.ajax({
            url: url,
            method: $searchListform.prop('method'),
            data: serializedData,
        });
        const response = await $.ajax({
            url: '/ticket/proofCount',
            method: $searchListform.prop('method'),
            data: serializedData,
        });
        this.proofepcTarget.innerText = response['counts']['proofByEPC'];
        this.prooftamTarget.innerText = response['counts']['proofByTam'];
        this.proofg4nTarget.innerText = response['counts']['proofByG4N'];
        this.proofamTarget.innerText = response['counts']['proofByAM'];
        this.ignoredTarget.innerText = response['counts']['ignored'];
        this.proofmaintenanceTarget.innerText = response['counts']['proofByMaintenance'];

        if (newPlantId > 0) {
            let $button = $(this.listTarget).find('#newTicketBtn');
            $button.attr('disabled', false);
        }

        $(document).foundation();
        this.disableAllToolTips()
    }

    async update(event) {
        event.preventDefault();
        const $searchListform = $(this.searchBarTarget).find('form');
        let serializedData = $searchListform.serialize();
        let newPlantId = $(this.listTarget).find('#newPlantSelect').find(":selected").val()
        let url = this.urlSearchValue.concat('&newPlantId=', newPlantId);
        this.listTarget.innerHTML = await $.ajax({
            url: url,
            method: $searchListform.prop('method'),
            data: serializedData,
        });
        const response = await $.ajax({
            url: '/ticket/proofCount',
            method: $searchListform.prop('method'),
            data: serializedData,
        });
        this.proofepcTarget.innerText = response['counts']['proofByEPC'];
        this.prooftamTarget.innerText = response['counts']['proofByTam'];
        this.proofg4nTarget.innerText = response['counts']['proofByG4N'];
        this.proofamTarget.innerText = response['counts']['proofByAM'];
        this.ignoredTarget.innerText = response['counts']['ignored'];
        this.proofmaintenanceTarget.innerText = response['counts']['proofByMaintenance'];

        if (newPlantId > 0) {
            let $button = $(this.listTarget).find('#newTicketBtn');
            $button.attr('disabled', false);
        }

        this.disableAllToolTips()
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

        if ($(this.directionTarget).val() === '') {
            $(this.directionTarget).val('ASC');
        } else if ($(this.directionTarget).val() === 'ASC') {
            $(this.directionTarget).val('DESC');
        } else {
            $(this.directionTarget).val('ASC');
        }
        this.listTarget.innerHTML = await $.ajax({
            url: event.currentTarget.href,
        });
        $(document).foundation();
        this.disableAllToolTips()
    }

    async sortBegin(event) {
        $(this.sortTarget).val('ticket.begin');
        if ($(this.directionTarget).val() === '') {
            $(this.directionTarget).val('ASC');
        } else if ($(this.directionTarget).val() === 'ASC') {
            $(this.directionTarget).val('DESC');
        } else {
            $(this.directionTarget).val('ASC');
        }
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
        if ($(this.directionTarget).val() === '') {
            $(this.directionTarget).val('ASC');
        } else if ($(this.directionTarget).val() === 'ASC') {
            $(this.directionTarget).val('DESC');
        } else {
            $(this.directionTarget).val('ASC');
        }
        this.listTarget.innerHTML = await $.ajax({
            url: event.currentTarget.href,
        });
        $(document).foundation();
    }

    async sortUpdate(event) {
        event.preventDefault();
        $(this.sortTarget).val('ticket.updatedAt');
        if ($(this.directionTarget).val() === '') {
            $(this.directionTarget).val('ASC');
        } else if ($(this.directionTarget).val() === 'ASC') {
            $(this.directionTarget).val('DESC');
        } else {
            $(this.directionTarget).val('ASC');
        }
        this.listTarget.innerHTML = await $.ajax({
            url: event.currentTarget.href,
        });
        $(document).foundation();
        this.disableAllToolTips()
    }

    disableAllToolTips() {
        $("[id*='tooltip']").each(function () {
            $(this).css('display', 'none');
        });
    }

    async selectAnlage() {
        let id = $(this.anlageselectTarget).val();
        if (id != '') {
            $(this.InverterSearchButtonTarget).removeAttr('disabled');
            this.InverterSearchDropdownTarget.innerHTML = await $.ajax({
                url: '/list/getinverterarray/' + id,
            });
        } else {
            $(this.InverterSearchButtonTarget).attr('disabled', 'disabled');
        }
    }

    checkTrafo({params: {first, last, trafo}}) {
        let body = $(this.InverterSearchDropdownTarget);
        let checked = $("#search-trafo" + trafo).prop('checked');
        body.find('input:checkbox[class=js-checkbox]').each(function () {
            if ($(this).prop('id').substring(9) >= first) {
                if ($(this).prop('id').substring(9) <= last) {
                    if (checked) $(this).prop('checked', true);
                    else $(this).prop('checked', false);
                }
            }
        });
        $(this.switchTarget).prop('checked', false)
        this.setInverter()
    }

    setInverter() {
        let inverterString = '';
        let body = $(this.InverterSearchDropdownTarget);
        let counter = 0;
        body.find('input:checkbox[class=js-checkbox]:checked').each(function () {
            counter++;
            if (inverterString == '') {
                inverterString = inverterString + $(this).prop('id').substring(9);
            } else {
                inverterString = inverterString + ', ' + $(this).prop('id').substring(9);
            }
        });
        if (counter == body.find('input:checkbox[class=js-checkbox]').length) {
            inverterString = '*';
        }
        $('#inverterSearch').val(inverterString);
    }

    selectAll() {
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
            body.find('input:checkbox[class=js-checkbox]').each(function () {
                $(this).prop('checked', false);
            });
            inverterString = '';
        }
        $('#inverterSearch').val(inverterString);
    }

    async changeStatus(event) {
        let ticketList = [];
        $(this.listTarget).find('input:checkbox[class=js-multiselect-checkbox]:checked').each(function () {
            ticketList.push($(this).prop('value'));
        });
        let status = $(this.listTarget).find('#selectNewStatus').prop('value');
        let data = {'tickets' : ticketList.toString(), 'status' : status }
         await $.ajax({
            url: '/ticket/statusChange',
             method: 'GET',
            data: data,
        });
        await this.update(event);
    }
}