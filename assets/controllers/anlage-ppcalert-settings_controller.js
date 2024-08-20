import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';

export default class extends Controller {
    static targets = ['behavior', 'settings'];
    static values = {

    }
    connect() {
        //console.log(this.settingsTarget, this.behaviorTarget);
        this.changeVisibility();
        this.onChangeReplaceBy()
    }

    onChangeBehavior(){
        let settings = Array.from(this.settingsTarget.getElementsByClassName('js-settings'));
        settings.forEach((element) => {
            element.removeAttribute('disabled');
        });
        this.changeVisibility();
    }

    onChangeReplaceBy() {
        const ppcAutoTicketReplaceBy = this.settingsTarget.querySelector("#anlage_form_settings_ppcAutoTicketReplaceBy");
        const ppcAutoTicketReplaceIrr = this.settingsTarget.querySelector("#anlage_form_settings_ppcAutoTicketReplaceIrr");
        const ppcAutoTicketUseHour = this.settingsTarget.querySelector("#anlage_form_settings_ppcAutoTicketUseHour");
        const ppcAutoTicketPaBehavior = this.settingsTarget.querySelector("#anlage_form_settings_ppcAutoTicketPaBehavior");
        switch (ppcAutoTicketReplaceBy.value) {
            case 'g4n_exp':
                ppcAutoTicketReplaceIrr.value = '0';
                ppcAutoTicketUseHour.value = '0';
                ppcAutoTicketReplaceIrr.setAttribute('disabled', 'disabled');
                ppcAutoTicketUseHour.setAttribute('disabled', 'disabled');
                ppcAutoTicketPaBehavior.removeAttribute('disabled');
                break;
            case 'pvsyst':
                ppcAutoTicketReplaceIrr.removeAttribute('disabled');
                ppcAutoTicketUseHour.removeAttribute('disabled');
                ppcAutoTicketPaBehavior.removeAttribute('disabled');
                break;
            default:
                ppcAutoTicketReplaceIrr.setAttribute('disabled', 'disabled');
                ppcAutoTicketUseHour.setAttribute('disabled', 'disabled');
                ppcAutoTicketPaBehavior.setAttribute('disabled', 'disabled');
        }
    }

    changeVisibility() {
        const behavior = $(this.behaviorTarget).find('#anlage_form_settings_ppcAutoTicketBehavior').val();
        const ppcAutoTicketReplaceBy = this.settingsTarget.querySelector("#anlage_form_settings_ppcAutoTicketReplaceBy");
        const ppcAutoTicketReplaceIrr = this.settingsTarget.querySelector("#anlage_form_settings_ppcAutoTicketReplaceIrr");
        const ppcAutoTicketUseHour = this.settingsTarget.querySelector("#anlage_form_settings_ppcAutoTicketUseHour");
        const ppcAutoTicketPaBehavior = this.settingsTarget.querySelector("#anlage_form_settings_ppcAutoTicketPaBehavior");
        let settings = [];
        switch (behavior) {
            case 'nothing':
                // disable all elments
                settings = Array.from(this.settingsTarget.getElementsByClassName('js-settings'));
                ppcAutoTicketReplaceBy.value = '';
                ppcAutoTicketReplaceIrr.value = '0';
                ppcAutoTicketUseHour.value = '0';
                ppcAutoTicketPaBehavior.value = ''
                break;
            case 'replace':
                //disable all elments wich are not necessary for 'replace'
                settings = Array.from(this.settingsTarget.getElementsByClassName('js-disable-replace'));
                ppcAutoTicketReplaceIrr.value = 0
                break;
            case 'exclude':
                //disable all elments wich are not necessary for 'exclude'
                settings = Array.from(this.settingsTarget.getElementsByClassName('js-disable-exclude'));
                break;
        }
        settings.forEach((element) => {
            element.setAttribute('disabled', 'disabled');
        });
    }
}
