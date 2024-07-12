import { Controller } from '@hotwired/stimulus';
import { Foundation } from 'foundation-sites/js/foundation';

export default class extends Controller {
    static targets = ['content'];
    static values = {
        url: String,
        refreshIntervale: Number
    }

    connect() {
        this.load()
        if (this.hasRefreshIntervaleValue) {
            this.startRefreshing()
        }
    }

    disconnect() {
        this.stopRefreshing()
    }

    load() {
        let today = new Date();
        let time = ("0" + today.getHours()).slice (-2) + ":" + ("0" + today.getMinutes()).slice (-2) + ":" + ("0" + today.getSeconds()).slice (-2);
        this.contentTarget.innerHTML = time;

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
}
