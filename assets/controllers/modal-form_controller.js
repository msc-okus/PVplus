import { Controller } from '@hotwired/stimulus';
import {Reveal} from 'foundation-sites';

export default class extends Controller {
    static targets = ['modal'];
        openModal(event) {
            var Modal = new Reveal(this.modalTarget, 'open');
            }
}