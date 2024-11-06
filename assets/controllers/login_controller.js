import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';

export default class extends Controller {

    connect() {

    }
    async sendMail(event) {
        event.preventDefault();

        await $.ajax({
            url: '/autentication/2fa/onetimepw'
        });

    }

    submit(event){
        event.preventDefault();
        const $authArea = $('#authArea');
        // all input fields
        const $auth_code = $authArea.find('#_auth_code');

        // input fields
        const $first = $authArea.find('[name=pincode-1]')
            , $second = $authArea.find('[name=pincode-2]')
            , $third = $authArea.find('[name=pincode-3]')
            , $fourth = $authArea.find('[name=pincode-4]')
            , $fifth = $authArea.find('[name=pincode-5]')
            , $sixth = $authArea.find('[name=pincode-6]')
        ;

        $auth_code.val($first.val()+$second.val()+$third.val()+$fourth.val()+$fifth.val()+$sixth.val());

        $('form').submit();
    }
}