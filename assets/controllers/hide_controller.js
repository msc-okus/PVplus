import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    stateHide = null;

    static targets = [
        'area', 'switch'
    ];

    connect() {
        this.areaTarget.classList.add('hide');
    }

    change() {
        if (this.areaTarget.classList.contains('hide')) {
            this.areaTarget.classList.remove('hide');
        } else {
            this.areaTarget.classList.add('hide');
        }
    }

}
