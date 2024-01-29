import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
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
}