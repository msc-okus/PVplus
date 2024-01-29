import { Controller } from 'stimulus';
import $ from 'jquery';
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
        var messagebutton = document.getElementById('far-fa-bell');

        //messageelem.style.display = 'block';
        this.fadeInElement(messageelem);
        messagetext.innerText = this.messageValue;
        pdfdownload.innerHTML = '<div>You can dowanload it here: <a class="hollow button tiny action-icon shadow" href="/reporting/pdf/'+this.prozessidValue+'" target="_blank"><span style="background-color:#ffffff" class="fa fa-file-pdf"></span></a></div>'

        messagebutton.style.c = 'block';
        window.setTimeout(() => {
            this.fadeOutElement(messageelem);
        }, 10000);
        //alert(this.messageValue);
    }

    fadeInElement(element) {
        element.classList.remove('fade');
    }

    fadeOutElement(element) {
        element.classList.add('fade');
    }

    toggleElementFade(element) {
        element.classList.toggle('fade');
    }


    toggletabs(event) {
        event.preventDefault();
        const value = event.target.dataset.value;
        //alert(value);

        $('#messagestabs div').addClass('fade');
        $('#messagestabs div').attr("style","display:none !important");
        $('.messagebuttons ul li').removeClass('is-active');
        $('.messagebuttons ul li').addClass('is-inactive');

        var tabtoshow = document.getElementById(value);
        $('#'+value).attr("style","display:block !important");
        $('#'+value).removeClass('fade');
        $('.messagebuttons ul #li'+value).removeClass('is-inactive');
        $('.messagebuttons ul #li'+value).addClass('is-active');
        this.fadeInElement(tabtoshow);

    }
}
