import { Controller } from '@hotwired/stimulus';
import { useDispatch } from 'stimulus-use';
import $ from 'jquery';

export default class extends Controller {
    static targets = ['list', 'reportForm', 'searchForm', 'createForm', 'required', 'deactivable'];
    static values = {
        urlCreate: String,
        urlSearch: String,
    }
    myData = null;

    connect() {
        useDispatch(this);
    }

    toggle(){
        const $button = $(this.deactivableTargets);
        if ($button.attr('disabled')) {
            $button.removeAttr('disabled')
        } else {
           $button.attr('disabled', 'disabled')
        }
    }

    handleInput() {
        const $button = $(this.deactivableTargets);
        const isRequiredFilled = this.requiredTargets.every(el => el.value);
        if (isRequiredFilled) {
            $button.removeAttr('disabled')
        } else {
            $button.attr('disabled', 'disabled')
        }
    }

    async search(event){
        event.preventDefault();
        const $searchReportform = $(this.reportFormTarget).find('form');
        this.listTarget.innerHTML = await $.ajax({
            url: this.urlSearchValue,
            method: $searchReportform.prop('method'),
            data: $searchReportform.serialize(),
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

    async create(event) {
        event.preventDefault();
        const $createReportform = $(this.reportFormTarget).find('form');

        this.listTarget.innerHTML = await $.ajax({
            url: this.urlCreateValue,
            method: $createReportform.prop('method'),
            data: $createReportform.serialize(),
            beforeSend: function(){
                $('.ajax-loader').css("visibility", "visible");
            },
            complete: function(){
                $('.ajax-loader').css("visibility", "hidden");
            }
        });
        this.dispatch('success');
    }

}
