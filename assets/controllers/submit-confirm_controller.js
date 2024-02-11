import { Controller } from '@hotwired/stimulus';
import Swal from 'sweetalert2';
import $ from 'jquery';
import { useDispatch } from "stimulus-use";

export default class extends Controller {
    static values = {
        id: String,
        title: String,
        text: String,
        icon: String,
        confirmButtonText: String,
        redirectUrl: String,
    }

    connect() {
        useDispatch(this);
    }

    onSubmit(event) {
        event.preventDefault();
        var title = this.titleValue;
        if ($('#titletextflex').length > 0) {
            var titletext = document.getElementsByClassName('titletextflex')[0];
            if (titletext.textContent != '') {
                var title = titletext.textContent;
            }
        }
        Swal.fire({
            title: title || null,
            text: this.textValue || null,
            icon: this.iconValue || null,
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: this.confirmButtonTextValue || 'Yes &nbsp;<i class="fa fa-paper-plane" aria-hidden="true"></i>',
            preConfirm: () => {
                return this.confirmAction();
            }
        })
    }

    async confirmAction(result) {
        if (this.redirectUrlValue) {
            const response = await $.ajax(this.redirectUrlValue);
            this.dispatch('async:submitted', {
                response
            });
        } else {
            this.element.submit();
        }

    }

}
