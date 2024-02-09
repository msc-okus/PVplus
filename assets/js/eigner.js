import $ from 'jquery';
let $wrapper_contacts = $('.js-contact-wrapper');
$wrapper_contacts.on('click', '.js-remove-contact', function (e) {
    e.preventDefault();
    $(this).closest('.js-contact-item')
        .remove();
    //console.log("updated");
});
$wrapper_contacts.on('click', '.js-add-contact', function (e) {
    e.preventDefault();
    let prototype = $wrapper_contacts.data('prototype');
    let index = $wrapper_contacts.data('index');
    let newForm = prototype.replace(/__name__/g, index);
    $wrapper_contacts.data('index', index + 1);
    $('#contact-table>tbody').append(newForm);
    //console.log("updated");
});
