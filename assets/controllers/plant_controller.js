import { Controller } from '@hotwired/stimulus';

import $ from 'jquery';


export default class extends Controller {
    static targets = ['activateTicket', 'ticket'];

    connect(){
        const $button = $(this.ticketTargets);
        $button.attr('disabled', 'disabled');
    }

    activateTicket() {
        const $button = $(this.ticketTargets);

        const ticketActivated = this.activateTicketTargets[0].checked;
        if (ticketActivated) {
            $button.removeAttr('disabled');
        } else {
            $button.attr('disabled', 'disabled');
        }

    }

}