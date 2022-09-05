import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';
import {Reveal} from "foundation-sites";
import {useDispatch} from "stimulus-use";

export default class extends Controller {
    static targets = ['list', 'searchBar', 'modalCreate', 'modalCreateBody', 'AlertFormat','AlertDates', 'saveButton', 'formBegin', 'formEnd'];
    static values = {
        urlCreate: String,
        urlSearch: String,
    }

    connect() {
        useDispatch(this);
    }

    async search(event){
        event.preventDefault();
        console.log(
            'Ja'
        );
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

    check() {
        console.log( $(this.saveButtonTarget),  $(this.splitAlertFormatTarget)
        );
        const valueBegin = $(this.formBeginTarget).prop('value');
        const valueEnd = $(this.formEndTarget).prop('value');
        console.log(valueBegin, valueEnd)

        const date1 = new Date(valueBegin);
        const date2 = new Date(valueEnd);
        date1.setSeconds(0);
        date2.setSeconds(0);
        const timestamp = date1.getTime();
        const timestamp2 = date2.getTime();


        if (timestamp2 >= timestamp){
            $(this.AlertDatesTarget).addClass('is-hidden');
            $(this.saveButtonTarget).removeAttr('disabled');
        }
        else {
            $(this.AlertDatesTarget).removeClass('is-hidden');
            $(this.saveButtonTarget).attr('disabled', 'disabled')
        }

        if ((timestamp % 900 === 0) && (timestamp2 % 900 === 0)){
            $(this.AlertFormatTarget).addClass('is-hidden');
            $(this.saveButtonTarget).removeAttr('disabled');
        } else {
            $(this.AlertFormatTarget).removeClass('is-hidden');
            $(this.saveButtonTarget).attr('disabled', 'disabled')
        }
        console.log($(this.AlertFormatTarget), $(this.AlertDatesTarget), $(this.saveButtonTarget))


    }


}