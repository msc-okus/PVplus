$(document).ready(function() {

    $('.js-autocomplete-user').each(function() {
        var autocompleteUrl = '/user/find';
        $(this).autocomplete({hint: false}, [
            {
                source: function(query, cb) {
                    $.ajax({
                        url: autocompleteUrl+'?query='+query
                    }).then(function(data) {
                       cb(data.userss);
                    });
                },
                displayKey: 'name',
                debounce: 500 // only request every 1/2 second
            }
        ]);
    });
});