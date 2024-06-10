import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';
import {Reveal} from "foundation-sites";
import {useDispatch} from "stimulus-use";

export default class extends Controller {
    static targets = ['list', 'searchBar', 'uploadForm'];
    static values = {
        urlSearch: String,
    }

    async search(event) {
        event.preventDefault();
        const $searchListform = $(this.searchBarTarget).find('form');
        let serializedData = $searchListform.serialize().concat("&page=1");
        this.listTarget.innerHTML = await $.ajax({
            url: this.urlSearchValue,
            method: $searchListform.prop('method'),
            data: serializedData,
        });
        $(document).foundation();

    }
    async upload(event){
        const $uploadForm = $(this.uploadFormTarget).find('form');
        let serializedData = $uploadForm.serialize();
        //console.log($uploadForm);
         await $.ajax({
            url: this.urlUploadValue,
            method: 'GET',//$uploadForm.prop('method'),
            data: serializedData,
        });
         this.search();
    }
    async update(event){
        event.preventDefault();
        const $searchListform = $(this.searchBarTarget).find('form');
        let serializedData = $searchListform.serialize();
        this.listTarget.innerHTML = await $.ajax({
            url: this.urlSearchValue,
            method: $searchListform.prop('method'),
            data: serializedData,
        });
        $(document).foundation();
    }

}