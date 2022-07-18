import { Controller } from '@hotwired/stimulus';
import { useDispatch } from 'stimulus-use';
import { Reveal } from 'foundation-sites';
import $ from 'jquery';



export default class extends Controller {

    static targets = ['splitModal', 'splitForm'];

    static values = {
        urlSplit: String,
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

    async splitTicket() {
        try {
            await $.ajax({
                url: this.urlSplitValue,
                data: $(this.splitFormTarget).find('.js-split-ticket').serialize()
            });
            this.dispatch('async:submitted');
            //this.splitModal.destroy();
        } catch(e) {
            console.log(e);
        }

    }

}