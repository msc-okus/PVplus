$(document).ready(function() {

    $('.input-group-field').each(function() {
        var autocompleteUrl = '/api/anlages.json';
    $(this).autocomplete({hint: false}, [
        {
            source: function(query, cb) {
                $.ajax({
                    url: autocompleteUrl+'?anlName='+query
                }).then(function(data) {
                    cb(data);
                });
            },
            displayKey: 'anlName',
            debounce: 500 // only request every 1/2 second

        }
    ]);
    })
});