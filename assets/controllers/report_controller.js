import { Controller } from '@hotwired/stimulus';
import { useDispatch } from 'stimulus-use';
import $ from 'jquery';
import {Reveal} from "foundation-sites";

export default class extends Controller {
    static targets = ['list', 'reportForm', 'searchForm', 'createForm', 'required', 'deactivable', 'deactivable1', 'modalBody'];
    static values = {
        urlCreate: String,
        urlSearch: String,
    }

    connect() {
        useDispatch(this);
    }

    toggle(){
        const $button = $(this.deactivableTargets);
        const $button1 = $(this.deactivable1Targets);
        if ($button.attr('disabled')) {
            $button.removeAttr('disabled');
        } else {
            $button.attr('disabled', 'disabled');
        }
        if ($button1.attr('disabled')) {
            $button1.removeAttr('disabled');
        } else {
            $button.attr('disabled', 'disabled');
        }
    }

    handleInput() {
        const $button = $(this.deactivableTargets);
        const $button1 = $(this.deactivable1Targets);
        const isRequiredFilled = this.requiredTargets.every(el => el.value);
        if (isRequiredFilled) {
            $button.removeAttr('disabled');
            $button1.removeAttr('disabled');
        } else {
            $button.attr('disabled', 'disabled');
            $button.attr('disabled', 'disabled');
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
        const $queryParams = $(event.currentTarget).data('query-value');
        this.listTarget.innerHTML = await $.ajax({
            url: this.urlSearchValue,
            data: $queryParams,
        });
    }

    async sort(event) {
        event.preventDefault();
        this.listTarget.innerHTML = await $.ajax({
            url: event.currentTarget.href,
        });
        $(document).foundation();
    }

    async create(event) {
        event.preventDefault();
        const $createReportform = $(this.reportFormTarget).find('form');

        this.listTarget.innerHTML = await $.ajax({
            url: this.urlCreateValue,
            method: $createReportform.prop('method'),
            data: $createReportform.serialize(),
            beforeSend: function(){
                $('.ajax-loader').css('visibility', 'visible');
            },
            complete: function(){
                $('.ajax-loader').css('visibility', 'hidden');
            }
        });
        this.dispatch('success');
    }
    async createlocal(event) {
        event.preventDefault();
        const $createReportform = $(this.reportFormTarget).find('form');

        this.listTarget.innerHTML = await $.ajax({
            url: "/",
            method: $createReportform.prop('method'),
            data: $createReportform.serialize(),
            beforeSend: function(){
                $('.ajax-loader').css('visibility', 'visible');
            },
            complete: function(){
                $('.ajax-loader').css('visibility', 'hidden');
            }
        });
        this.dispatch('success');
    }
    async openModal(event) {
        this.modalBodyTarget.innerHTML = 'Loading ...';
        this.modal = new Reveal($(this.modalTarget));
        this.modal.open();
        if (this.formUrlValue === '/ticket/create') {
            this.modalBodyTarget.innerHTML = await $.ajax({
                url: this.formUrlValue,
                data: {'anlage': $(this.anlageTarget).val()},
            });
            $(this.saveButtonTarget).attr('disabled', 'disabled');
        } else {
            this.modalBodyTarget.innerHTML = await $.ajax({
                url: this.formUrlValue,
            });
        }

        $(this.modalBodyTarget).foundation();
    }
}

