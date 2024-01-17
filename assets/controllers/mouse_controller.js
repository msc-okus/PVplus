import { Controller } from 'stimulus';

export default class extends Controller {
    static values = {
        message: String
    }
    connect() {

        var link = document.getElementById('messageProzessReady');
        link.style.display = 'block';
        alert(this.messageValue);
    }
}