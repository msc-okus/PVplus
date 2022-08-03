import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';
import {Reveal} from "foundation-sites";

export default class extends Controller {
    static targets = ['list', 'searchBar', 'modalCreate', 'modalCreateBody'];
    static values = {
        urlCreate: String,
        urlSearch: String,
    }

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

    async createTicket({params: {url}}) {
        this.modalCreateBodyTarget.innerHTML = 'Loading ...';
        //console.log($(this.modalCreateTarget));
        this.modal = new Reveal($(this.modalCreateTarget));
        this.modal.open();

        this.modalCreateBodyTarget.innerHTML = await $.ajax(url);

    }

    async saveTicket({params: {url}}) {
        event.preventDefault();
        const  $form = $(this.modalCreateBodyTarget).find('form');
        console.log($form);

        try {
            await $.ajax({
                url: url,
                method: $form.prop('method'),
                data: $form.serialize(),
            });
            this.modal.destroy();
        } catch(e) {
            this.modalBodyTarget.innerHTML = e.responseText;
        }




    }


}