import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';

export default class extends Controller {

    static targets = ['box'];

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
        const checkboxes = $(this.boxTargets);

        for (var i = 0; i < checkboxes.length; i++) {
            var checkbox = checkboxes[i];
            console.log(checkbox.checked);


        }
        /*
        $(checkboxes).addEventListener('change', () => {
           this.element.classList.add('checked');
        });
*/
    }

    submit() {

    }
    check(){

    }

}