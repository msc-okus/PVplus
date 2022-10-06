import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';
import {Reveal} from "foundation-sites";
import {useDispatch} from "stimulus-use";

export default class extends Controller {
    static targets = ['list', 'searchBar', 'modalCreate', 'modalCreateBody', 'AlertFormat', 'AlertDates', 'saveButton', 'formBegin', 'formEnd'];
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
        this.listTarget.innerHTML = await $.ajax({
            url: this.urlSearchValue,
            method: $searchListform.prop('method'),
            data: $searchListform.serialize(),
        });
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

    async sort(event) {
        event.preventDefault();
        this.listTarget.innerHTML = await $.ajax({
            url: this.urlSearchValue,
            data: event.currentTarget.attributes.href.value,
        });
        $(document).foundation();
    }
}