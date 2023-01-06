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
    static targets = ['modules', 'months']

    connect() {
        useDispatch(this);

    }

    onSubmit(event) {
        event.preventDefault();

        Swal.fire({
            title: this.titleValue || null,
            text: this.textValue || null,
            icon: this.iconValue || null,
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: this.confirmButtonTextValue || 'Yes',
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

    async removeFormElement(event){
        event.currentTarget.closest('tr').remove();
    }

    async addMonth(event){
        console.log('months');
        this.monthsTarget.innerHTML += event.currentTarget.dataset.prototype.replace(/__name__/g, event.currentTarget.dataset.index);
        event.currentTarget.dataset.index += 1;
    }

    async addModule(event){
        this.modulesTarget.innerHTML += event.currentTarget.dataset.prototype.replace(/__name__/g, event.currentTarget.dataset.index);
        event.currentTarget.dataset.index += 1;
    }

}
