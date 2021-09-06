jQuery(document).ready(function() {
    var $wrapper_contact = $('.js-access-list-wrapper');
    $wrapper_contact.on('click', '.js-remove-access', function(e) {
        e.preventDefault();
        $(this).closest('.js-access-item')
            .remove();
    });
    $wrapper_contact.on('click', '.js-add-accesst', function(e) {
        e.preventDefault();
        var prototype = $wrapper_contact.data('prototype');
        var index = $wrapper_contact.data('index');
        var newForm = prototype.replace(/__name__/g, index);
        $wrapper_contact.data('index', index + 1);
        $('#access>tbody').append(newForm);
    });

});