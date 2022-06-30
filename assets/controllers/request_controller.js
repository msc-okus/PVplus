import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';
import { Reveal } from 'foundation-sites';

export default class extends Controller {

    static targets = ['box', 'modal', 'modalBody'];

    connect() {}

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
        //console.log(response);

       var modal = new Reveal($(this.modalTargets));
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