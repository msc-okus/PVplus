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

        /*
        $(checkboxes).addEventListener('change', () => {
           this.element.classList.add('checked');
        });
*/
    }

    submit() {
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
        $.ajax({
            url: '/ticket/join',
            type: 'POST',
            contentType: 'application/json; charset=utf-8',
            data: jsonString
        });

        console.log(array);
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