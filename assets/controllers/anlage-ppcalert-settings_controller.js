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
        console.log(this.settingsTarget.querySelector("#anlage_form_settings_ppcAutoTicketReplaceBy").value);
        this.changeVisibility();
    }

    onChangeReplaceBy() {
        const replaceBy = this.settingsTarget.querySelector("#anlage_form_settings_ppcAutoTicketReplaceBy");
        console.log(replaceBy.value);
        switch (replaceBy.value) {
            case 'g4n_exp':
                this.settingsTarget.querySelector("#anlage_form_settings_ppcAutoTicketReplaceIrr").value = '0';
                this.settingsTarget.querySelector("#anlage_form_settings_ppcAutoTicketUseHour").value = '0';
                this.settingsTarget.querySelector("#anlage_form_settings_ppcAutoTicketReplaceIrr").setAttribute('disabled', 'disabled');
                this.settingsTarget.querySelector("#anlage_form_settings_ppcAutoTicketUseHour").setAttribute('disabled', 'disabled');
                this.settingsTarget.querySelector("#anlage_form_settings_ppcAutoTicketPaBehavior").removeAttribute('disabled');
                break;
            case 'pvsyst':
                this.settingsTarget.querySelector("#anlage_form_settings_ppcAutoTicketReplaceIrr").removeAttribute('disabled');
                this.settingsTarget.querySelector("#anlage_form_settings_ppcAutoTicketUseHour").removeAttribute('disabled');
                this.settingsTarget.querySelector("#anlage_form_settings_ppcAutoTicketPaBehavior").removeAttribute('disabled');
                break;
            default:
                this.settingsTarget.querySelector("#anlage_form_settings_ppcAutoTicketReplaceIrr").setAttribute('disabled', 'disabled');
                this.settingsTarget.querySelector("#anlage_form_settings_ppcAutoTicketUseHour").setAttribute('disabled', 'disabled');
                this.settingsTarget.querySelector("#anlage_form_settings_ppcAutoTicketPaBehavior").setAttribute('disabled', 'disabled');
        }
    }

    changeVisibility() {
        const behavior = $(this.behaviorTarget).find('#anlage_form_settings_ppcAutoTicketBehavior').val();
        let settings = [];
        console.log(behavior);
        switch (behavior) {
            case 'nothing':
                // disable all elments
                settings = Array.from(this.settingsTarget.getElementsByClassName('js-settings'));
                this.settingsTarget.querySelector("#anlage_form_settings_ppcAutoTicketReplaceBy").value = '';
                this.settingsTarget.querySelector("#anlage_form_settings_ppcAutoTicketReplaceIrr").value = '0';
                this.settingsTarget.querySelector("#anlage_form_settings_ppcAutoTicketUseHour").value = '0';
                this.settingsTarget.querySelector("#anlage_form_settings_ppcAutoTicketPaBehavior").value = ''
                break;
            case 'replace':
                //disable all elments wich are not necessary for 'replace'
                settings = Array.from(this.settingsTarget.getElementsByClassName('js-disable-replace'));
                this.settingsTarget.querySelector("#anlage_form_settings_ppcAutoTicketReplaceIrr").value = 0
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
