import { Controller } from '@hotwired/stimulus';
import { useDispatch } from 'stimulus-use';
import $ from 'jquery';
import {Reveal} from "foundation-sites";
import Swal from 'sweetalert2';

export default class extends Controller {
    static targets = ['list', 'searchForm','creationForm'];
    static values = {
        urlCreate: String,
        urlSearch: String,
    }
    async search(event){
        event.preventDefault();
        const $searchReportform = $(this.searchFormTarget).find('form');
        this.listTarget.innerHTML = await $.ajax({
            url: this.urlSearchValue,
            method: $searchReportform.prop('method'),
            data: $searchReportform.serialize(),
        });
        $(document).foundation();
    }

}
