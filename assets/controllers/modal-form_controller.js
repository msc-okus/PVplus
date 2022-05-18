import { Controller } from '@hotwired/stimulus';
import {Reveal} from 'foundation-sites';
import {foundation} from 'foundation-sites';


export default class extends Controller {

    static targets = ['modal'];

    openModal(event) {
        console.log(event);
        const elem = new Reveal(this.modalTarget, 'open');
       //$('#elem').foundation('open');

    }
}