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

    async splitTicket({params: {id}}) {
        console.log(id);
        console.log($(this.splitModalTarget));
        console.log('hola');
        if ($(this.splitFormTarget).find('.'+id).serialize() != "") {
            try {
                await $.ajax({
                    url: this.urlSplitValue,
                    data: $(this.splitFormTarget).find('.' + id).serialize()
                });
                this.dispatch('async:submitted');
                //this.splitModal.destroy();
            } catch (e) {
                console.log(e);
            }
        }

    }

}