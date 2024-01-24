import { Controller } from 'stimulus';

export default class extends Controller {

    static values = {
        message: String,
        prozesstype: String,
        prozessid: Number
    }

    connect() {
        var messageelem = document.getElementById('messageProzessReady');
        var messagetext = document.getElementById('messagetext');
        var pdfdownload = document.getElementById('pdfdownload');

        messageelem.style.display = 'block';

        messagetext.innerText = this.messageValue;
        pdfdownload.innerHTML = '<div><a class="hollow button tiny action-icon shadow" href="/reporting/pdf/'+this.prozessidValue+'" target="_blank"><span style="background-color:#ffffff" class="fa fa-file-pdf"></span></a></div>'

        window.setTimeout(() => {
            messageelem.style.display = 'none';
        }, 10000);
        //alert(this.messageValue);
    }

}