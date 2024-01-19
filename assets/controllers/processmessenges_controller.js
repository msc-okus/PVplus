import { Controller } from 'stimulus';

export default class extends Controller {

    static values = {
        message: String,
        url: String,
        refreshIntervale: Number
    }

    connect() {
        this.load()
        if (this.hasRefreshIntervaleValue) {
            this.startRefreshing()
        }
    }

    load() {
        fetch(this.urlValue)
            .then(response => response.text())
            .then(html => this.element.innerHTML = html)
    }

    startRefreshing() {
        this.refreshTimer = setInterval(() => {
            this.load()
        }, this.refreshIntervaleValue)
    }

    stopRefreshing() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer)
        }
    }

    showmessage() {
        alert(this.messageValue);
        var messageelem = document.getElementById('messageProzessReady');
        messageelem.style.display = 'block';

        messageelem.innerText = this.messageValue;

        window.setTimeout(() => {
            messageelem.style.display = 'none';
        }, 10000);
        //alert(this.messageValue);

    }
}