import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';

export default class extends Controller {
    static targets = ['behavior', 'settings'];
    static values = {

    }
    connect() {
        //console.log(this.settingsTarget, this.behaviorTarget);
        this.changeVisibility();
    }

    onChangeBehavior(){
        let settings = Array.from(this.settingsTarget.getElementsByClassName('js-settings'));
        settings.forEach((element) => {
            element.removeAttribute('disabled');
        });
        this.changeVisibility();
    }

    changeVisibility() {
        const behavior = $(this.behaviorTarget).find('#anlage_form_settings_ppcAutoTicketBehavior').val();
        let settings = [];

        switch (behavior) {
            case 'nothing':
                // disable all elments
                settings = Array.from(this.settingsTarget.getElementsByClassName('js-settings'));
                break;
            case 'replace':
                //disable all elments wich are not necessary for 'replace'
                settings = Array.from(this.settingsTarget.getElementsByClassName('js-exclude-disable'));
                break;
            case 'exclude':
                //disable all elments wich are not necessary for 'exclude'
                settings = Array.from(this.settingsTarget.getElementsByClassName('js-replace-disable'));
                break;
        }
        settings.forEach((element) => {
            element.setAttribute('disabled', 'disabled');
        });
    }
}
