import { Controller } from 'stimulus';

export default class extends Controller {
    static targets = ['submenu']

    here() {
        //alert('eins');
        var link = document.getElementById('x');
        link.style.display = 'block';
    }

    gone() {
        //alert('zwei');
        var link = document.getElementById('x');
        link.style.display = 'none';
    }
}