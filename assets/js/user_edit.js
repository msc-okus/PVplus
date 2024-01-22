import $ from 'jquery';

$(document).ready(function() {
    let $wrapper_contact = $('.js-access-list-wrapper');
    $wrapper_contact.on('click', '.js-remove-access', function(e) {
        e.preventDefault();
        $(this).closest('.js-access-item')
            .remove();
    });
    $wrapper_contact.on('click', '.js-add-accesst', function(e) {
        e.preventDefault();
        let prototype = $wrapper_contact.data('prototype');
        let index = $wrapper_contact.data('index');
        let newForm = prototype.replace(/__name__/g, index);
        $wrapper_contact.data('index', index + 1);
        $('#access>tbody').append(newForm);
    });

});