import { Controller } from '@hotwired/stimulus';
import {Modal} from 'foundation-sites';

export default class extends Controller {
        openModal(event) {
            console.log(event);
        }
}