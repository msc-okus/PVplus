import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';
import {Reveal} from "foundation-sites";
import {useDispatch} from "stimulus-use";

export default class extends Controller {
    static targets = ['list', 'searchBar', 'modalCreate', 'modalCreateBody', 'AlertFormat', 'AlertDates', 'saveButton', 'formBegin', 'formEnd', 'sort', 'direction'];
    static values = {
        urlCreate: String,
        urlSearch: String,
    }

    connect() {
        useDispatch(this);
    }

    async search(event) {
        event.preventDefault();
        const $searchListform = $(this.searchBarTarget).find('form');
        console.log($(this.directionTarget).val());
        let param = await $.ajax({
            url: this.urlSearchValue,
            method: $searchListform.prop('method'),
            data: $searchListform.serialize(),
        });
        this.listTarget.innerHTML = param;
        $(document).foundation();
    }

    async page(event) {
        event.preventDefault();
        const $queryParams = $(event.currentTarget).data("query-value");
        this.listTarget.innerHTML = await $.ajax({
            url: this.urlSearchValue,
            data: $queryParams,
        });
        $(document).foundation();
    }
    async sortId(event) {
        event.preventDefault();
        $(this.sortTarget).val('ticket.id');
        console.log($(this.directionTarget).val());
        if ($(this.directionTarget).val() == '') {$(this.directionTarget).val('ASC');}
        else if ($(this.directionTarget).val() == 'ASC'){$(this.directionTarget).val('DESC');}
        else {$(this.directionTarget).val('ASC');}
        console.log($(this.directionTarget).val());
        this.listTarget.innerHTML = await $.ajax({
            url: event.currentTarget.href,

        });
        $(document).foundation();
    }

    async sortBegin(event) {
        $(this.sortTarget).val('ticket.begin');
        if ($(this.directionTarget).val() == '') {$(this.directionTarget).val('ASC');}
        else if ($(this.directionTarget).val() == 'ASC'){$(this.directionTarget).val('DESC');}
        else {$(this.directionTarget).val('ASC');}
        event.preventDefault();
        this.listTarget.innerHTML = await $.ajax({
            url: event.currentTarget.href,

                    });
        $(document).foundation();
    }
    async sortEnd(event) {
        event.preventDefault();
        $(this.sortTarget).val('ticket.end');
        if ($(this.directionTarget).val() == '') {$(this.directionTarget).val('ASC');}
        else if ($(this.directionTarget).val() == 'ASC'){$(this.directionTarget).val('DESC');}
        else {$(this.directionTarget).val('ASC');}
        this.listTarget.innerHTML = await $.ajax({
            url: event.currentTarget.href,

        });
        $(document).foundation();
    }
    async sortUpdate(event) {
        event.preventDefault();
        $(this.sortTarget).val('ticket.updatedAt');
        if ($(this.directionTarget).val() == '') {$(this.directionTarget).val('ASC');}
        else if ($(this.directionTarget).val() == 'ASC'){$(this.directionTarget).val('DESC');}
        else {$(this.directionTarget).val('ASC');}
        this.listTarget.innerHTML = await $.ajax({
            url: event.currentTarget.href,

        });
        $(document).foundation();
    }
}