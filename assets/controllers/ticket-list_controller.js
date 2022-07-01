import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';
import {Reveal} from "foundation-sites";

export default class extends Controller {
    static targets = ['list', 'searchBar', 'newSplitModal'];
    static values = {
        urlCreate: String,
        urlSearch: String
    }

    splitModal = null;

    connect() {}

    async search(event){
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
    }

    async sort(event) {
        event.preventDefault();
        const $queryParams = $(event.currentTarget).data("query-value");
        this.listTarget.innerHTML = await $.ajax({});
    }

    async createTicket(event) {
        event.preventDefault();

    }

    openSplitTicket(event){
        event.preventDefault();
        this.splitModal = new Reveal($(this.newSplitModalTarget));
        this.splitModal.open();
    }

    closeSplitTicket(event){
        event.preventDefault();
        this.splitModal.destroy();
    }

    async splitTicket(event) {
        event.preventDefault();
    }
}