import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';
import { Reveal } from 'foundation-sites';

export default class extends Controller {

    static targets = ['box', 'modal', 'modalBody'];

    connect() {
        // const button = $(this.joinTargets);
        //const button = this.element.children.namedItem('join');
        /*
        var array = [];
        //const checkboxes = this.element.querySelectorAll('input[type=checkbox]:checked');
        var checkboxes = this.element;

        const jsonString = JSON.stringify(array);
        console.log(jsonString);

        $(button).on('click', () => {
            this.element.innerHTML = checkboxes.length.toString();
        });
        */

        /*
        $(checkboxes).addEventListener('change', () => {
           this.element.classList.add('checked');
        });
*/
    }

    async submit() {
        var array = [];
        const checkboxes = $(this.boxTargets);
        console.log(checkboxes);
        for (var i = 0; i < checkboxes.length; i++) {
            var checkbox = checkboxes[i];
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
        console.log(response);

        this.modal = new Reveal($(this.modalTargets));

        this.modal.open();

        $(this.modalBodyTargets).innerHTML = response;
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