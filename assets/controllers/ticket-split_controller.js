import { Controller } from '@hotwired/stimulus';
import { useDispatch } from 'stimulus-use';
import { Reveal } from 'foundation-sites';
import $ from 'jquery';



export default class extends Controller {

    static targets = ['splitModal', 'splitForm', 'splitDelete'];

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

    closeSplitTicket(event){
        event.preventDefault();
        //this.splitModal.destroy();
        this.dispatch('async:submitted');
    }

    async splitTicket({params: {id}}) {
        if ($(this.splitFormTarget).find('.'+id).serialize() != "") {
            try {
                await $.ajax({
                    url: this.urlSplitValue,
                    data: $(this.splitFormTarget).find('.' + id).serialize()
                });
                this.dispatch('async:submitted');
            } catch (e) {
                console.log(e);
            }
        }

    }
    async delete({params: {id}}){
        var data = {'value': $(this.splitDeleteTarget).find('.select-' + id).val()}
        try {
            await $.ajax({
                url: this.urlDeleteValue,
                data: data
            });
            this.dispatch('async:submitted');
        } catch (e) {
            console.log(e);
        }
    }

}