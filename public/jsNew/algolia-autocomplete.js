$(document).ready(function() {

    $('.input-group-field').each(function() {
        var autocompleteUrl = '/reporting/anlagen/find';
    $(this).autocomplete({hint: false}, [
        {
            source: function(query, cb) {
                $.ajax({
                    url: autocompleteUrl+'?query='+query
                }).then(function(data) {
                    cb(data.anlagen);
                });
            },
            displayKey: 'anlName',
            debounce: 500 // only request every 1/2 second

        }
    ]);
    })
});