import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';

export default class extends Controller {
    static targets = ['content'];
    static values = {
        url: String,
    }

    async refreshContent(event) {
        this.contentTarget.innerHTML = await $.ajax({
            url: this.urlValue,
        });
    }
}
