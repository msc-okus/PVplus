import { Controller } from '@hotwired/stimulus';
import { useDispatch } from 'stimulus-use';
import { Reveal } from 'foundation-sites';
import $ from 'jquery';

export default class extends Controller {
    static targets = ['splitModal', 'splitForm', 'splitDelete', 'splitAlert', 'splitButton', 'splitAlertFormat'];
    static values = {
        urlSplit: String,
        urlDelete: String,
    }

    connect() {
        useDispatch(this);
    }

    openSplitTicket(event){
        event.preventDefault();
        this.splitModal = new Reveal($(this.splitModalTarget));
        this.splitModal.open();
    }
    openDelete(event){
        event.preventDefault();
        this.splitModal = new Reveal($(this.splitDeleteTarget));
        this.splitModal.open();
    }

    closeSplitTicket(event){
        event.preventDefault();
        this.dispatch('async:submitted');
    }

    async splitTicket({params: {id}}) {

        const min = $(this.splitFormTarget).find('.' + id).prop('min');
        const max = $(this.splitFormTarget).find('.' + id).prop('max');
        const value = $(this.splitFormTarget).find('.' + id).prop('value');

        if(value < max && value > min) {
            if ($(this.splitFormTarget).find('.' + id).serialize() !== "") {
                try {
                    await $.ajax({
                        url: this.urlSplitValue,
                        data: $(this.splitFormTarget).find('.' + id).serialize()
                    });
                    this.dispatch('async:submitted');
                } catch (e) {
                    //console.log(e);
                }
            }
        } else {
            $(this.splitAlertTarget).removeClass('is-hidden');

        }

    }

    async delete({params: {id}}){
        const data = {'value': $(this.splitDeleteTarget).find('.select-' + id).val()};
        if (data !== "") {
            try {
                await $.ajax({
                    url: this.urlDeleteValue,
                    data: data
                });
                this.dispatch('async:submitted');
            } catch (e) {
                //console.log(e);
            }
        }
    }

    check({params: {id}}) {
        const min = $(this.splitFormTarget).find('.' + id).prop('min');
        const max = $(this.splitFormTarget).find('.' + id).prop('max');
        const value = $(this.splitFormTarget).find('.' + id).prop('value');

        const date1 = new Date(value);

        const timestamp = date1.getTime();
        //console.log();
        if (value < max && value > min) {
            $(this.splitAlertTarget).addClass('is-hidden');
            $(this.splitButtonTarget).removeAttr('disabled');
        } else {
            $(this.splitAlertTarget).removeClass('is-hidden');
            $(this.splitButtonTarget).attr('disabled', 'disabled')

            //console.log( $(this.splitButtonTarget))
        }
        if (timestamp % 900 === 0){
            $(this.splitAlertFormatTarget).addClass('is-hidden');
            $(this.splitButtonTarget).removeAttr('disabled');
        } else {
            $(this.splitAlertFormatTarget).removeClass('is-hidden');
            $(this.splitButtonTarget).attr('disabled', 'disabled')
        }
    }
}