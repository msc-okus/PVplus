import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';

export default class extends Controller {

    connect() {
        console.log('Geht');
    }
    async sendMail(event) {
        event.preventDefault();

        await $.ajax({
            url: '/autentication/2fa/onetimepw'
        });

    }
}
