import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';
export default class extends Controller {

    static values = {
        message: String,
        prozesstype: String,
        prozessid: Number
    }
    connect() {
        if(this.messageValue != ''){
            this.showReady();
        }
    }
    showReady() {
        let messageelem = document.getElementById('messageProzessReady');
        let messagetext = document.getElementById('messagetext');
        let pdfdownload = document.getElementById('pdfdownload');
        let messagebutton = document.getElementById('far-fa-bell');

        if (this.messageValue == 'empty'){
            messagebutton.style.color = '#1779ba';
            return; // process.exit(1);
        } else {
            messagebutton.style.color = '#ff0000';
        }

        messageelem.style.display = 'block';
        this.fadeInElement(messageelem);
        messagetext.innerText = this.messageValue;
        if (this.prozesstypeValue.includes('Report') ){
            pdfdownload.innerHTML = '<div>You can dowanload it here: <a class="hollow button tiny action-icon shadow" href="/reporting/pdf/'+this.prozessidValue+'" target="_blank"><span style="background-color:#ffffff" class="fa fa-file-pdf"></span></a></div>'
        } else {
            pdfdownload.innerHTML = '';
        }

        window.setTimeout(() => {
            this.fadeOutElement(messageelem);
        }, 15000);
    }

    fadeInElement(element) {
        $('#messageProzessReady').attr("style","top:5px !important");
        element.classList.remove('fade');
    }

    fadeOutElement(element) {
        element.classList.add('fade');
        $('#messageProzessReady').attr("style","top:-500px !important");
    }

    toggleElementFade(element) {
        element.classList.toggle('fade');
    }

    toggletabs(event) {
        event.preventDefault();
        const value = event.target.dataset.value;

        $('#messagestabs div').addClass('fade');
        $('#messagestabs div').attr("style","display:none !important");
        $('.messagebuttons ul li').removeClass('is-active');
        $('.messagebuttons ul li').addClass('is-inactive');

        let tabtoshow = document.getElementById(value);
        $('#'+value).attr("style","display:block !important");
        $('#'+value).removeClass('fade');
        $('.messagebuttons ul #li'+value).removeClass('is-inactive');
        $('.messagebuttons ul #li'+value).addClass('is-active');
        this.fadeInElement(tabtoshow);

    }
}