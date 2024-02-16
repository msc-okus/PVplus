import { Controller } from '@hotwired/stimulus';
import { useDispatch } from 'stimulus-use';
import { Reveal } from 'foundation-sites';
import  Swal  from 'sweetalert2';
import $ from 'jquery';
import axios from 'axios';

export default class extends Controller {
    static targets = ['modal', 'modalBody', 'modalContent', 'modalForecast'];
    static values = {
        formUrl: String,
    }
    modal = null;

    connect() {
        useDispatch(this);
    }

    openModal(event) {
        event.preventDefault();

            Swal.fire({
                    title: "Are you sure?",
                    text: "You want to build a new forecast DB!",
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonColor: "#126195",
                    timer: 80000,
                    confirmButtonText: "Yes, do it!",
                    cancelButtonText: "No, cancel it!",
                    showCloseButton: true,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    focusConfirm: true
                }).then((result) => {
                    if (result.isConfirmed) {
                       this.show_sweetmodal();
                    }
              });
    }

    show_sweetmodal() {
        var sts;
        Swal.fire({
            heightAuto: true,
            title: "Please wait don't close this window !",
            html:'<h6>We are now generating some data for you, <br> ' +
                'this can take a few minutes. <br>' +
                'This window will close automatically ! Thank you</h6>' +
                '<div class="sk-chase">\n' +
                ' <div class="sk-chase-dot"></div>\n' +
                ' <div class="sk-chase-dot"></div>\n' +
                ' <div class="sk-chase-dot"></div>\n' +
                ' <div class="sk-chase-dot"></div>\n' +
                ' <div class="sk-chase-dot"></div>\n' +
                ' <div class="sk-chase-dot"></div>\n' +
                '</div>',
            showCancelButton: false,
            showConfirmButton: false,
            showCloseButton: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            focusConfirm: false
        },$.ajax({
            type: "POST",
            url: this.formUrlValue,
            dataType : "json",
            success: function (data) {
                sts = data['status'];
                if (sts === 'good') {
                    Swal.close();
                  } else {
                    Swal.fire({
                        heightAuto: true,
                        title: "An error has occurred !",
                        icon: "error",
                        showCancelButton: true,
                        showConfirmButton: false,
                        showCloseButton: true,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        focusConfirm: false
                    });
                }
            },
            failure: function (data) {

                Swal.fire({
                    heightAuto: true,
                    title: "An error has occurred !",
                    icon: "error",
                    showCancelButton: true,
                    showConfirmButton: false,
                    showCloseButton: true,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    focusConfirm: false
                });
            },

        }));
    }

}
