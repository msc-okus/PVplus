import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';
import {Reveal} from "foundation-sites";
import {useDispatch} from "stimulus-use";

export default class extends Controller {
    static targets = ['list', 'searchBar', 'modalCreate', 'modalCreateBody', 'AlertFormat', 'AlertDates', 'saveButton', 'formBegin', 'formEnd', 'sort', 'direction', 'proofam', 'proofepc', 'prooftam', 'proofg4n', 'ignored', 'proofmaintenance'];
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
}