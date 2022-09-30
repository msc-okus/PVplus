import { Controller } from '@hotwired/stimulus';
import { useDispatch } from 'stimulus-use';
import { Reveal } from 'foundation-sites';
import $ from 'jquery';

export default class extends Controller {

    static targets = ['box', 'modal', 'modalBody'];

    connect() {
        useDispatch(this);
    }

    async submit() {
        let array = [];
        const checkboxes = $(this.boxTargets);

        for (let i = 0; i < checkboxes.length; i++) {
            let checkbox = checkboxes[i];
            if (checkbox.checked){
                array.push(checkbox.value);
            }
        }
        const jsonString = JSON.stringify(array);
        const response = await $.ajax({
            url: '/ticket/join',
            type: 'POST',
            data: jsonString
        });
        //console.log(response);

        let modal = new Reveal($(this.modalTargets));
        modal.open();
       //$(this.modalBodyTargets).innerHTML = response;


    }
    check(){
        if (!event.currentTarget.classList.contains('checked')) {
            event.currentTarget.classList.add('checked');
        }
        else {
            event.currentTarget.classList.remove('checked');
        }

    }

}