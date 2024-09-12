import $ from 'jquery';

let $wrapper_work = $('.js-notificationWork-wrapper');
$wrapper_work.on('click', '.js-remove-notificationWork', function(e) {
    e.preventDefault();
    $(this).closest('.js-notificationWork-item')
        .remove();
});
$wrapper_work.on('click', '.js-add-notificationWork', function(e) {
    e.preventDefault();
    let prototype = $wrapper_work.data('prototype');
    let index = $wrapper_work.data('index');
    let newForm = prototype.replace(/__name__/g, index);
    $wrapper_work.data('index', index + 1);
    $('#notification-work>tbody').append(newForm);
});