import { Controller } from '@hotwired/stimulus';
import axios from 'axios';

export default class extends Controller {
    static targets = [
        'anlage',
        'dcgroup'
    ];

    connect() {
        console.log('hey');
    }



    sortedByAnlage() {

        let form = this.anlageTarget.closest('form');
         let data = this.anlageTarget.name + '=' + this.anlageTarget.value;
         console.log(data);

        axios.post(form.action, data)
            .then(r => {

                let content = document.createElement('html');
                content.innerHTML = r.data;


                let newSelect = content.querySelector('[data-groups-target="dcgroup"]');

                document.querySelector('[data-groups-target="dcgroup"]').replaceWith(newSelect);

            }).catch(function (error) {
            console.log(error);
         });


    }

}
