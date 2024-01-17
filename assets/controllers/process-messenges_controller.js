import { Controller } from 'stimulus';

export default class extends Controller {
    static values = {
        message: String
    }
    connect() {

        var messageelem = document.getElementById('messageProzessReady');
        messageelem.style.display = 'block';
        messageelem.innerText = (this.messageValue);

        window.setTimeout(() => {
            messageelem.style.display = 'none';
        }, 10000);
        //alert(this.messageValue);

    }

}