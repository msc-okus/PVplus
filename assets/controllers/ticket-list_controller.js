import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';

export default class extends Controller {
    static targets = ['list', 'ticketForm', 'searchForm', 'createForm'];
    static values = {
        urlCreate: String,
        urlSearch: String
    }

    connect() {
    }

    async search(event){
        event.preventDefault();
        const $searchListform = $(this.ticketFormTarget).find('form');
        this.listTarget.innerHTML = await $.ajax({
            url: this.urlSearchValue,
            method: $searchListform.prop('method'),
            data: $searchListform.serialize(),
        });
    }

    async create(event) {
        event.preventDefault();
        /*
        this.listTarget.innerHTML = await $.ajax({
            beforeSend: function(){
                $('.ajax-loader').css("visibility", "visible");
            },
            //url: this.urlCreateValue,
            //method: $createReportform.prop('method'),
            //data: $createReportform.serialize(),
            complete: function(){
                $('.ajax-loader').css("visibility", "hidden");
            }
        });

         */
    }
}