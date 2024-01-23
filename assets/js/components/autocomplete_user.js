import $ from "jquery";
import 'autocomplete.js/dist/autocomplete.jquery';

$(document).ready(function() {
    $('.js-autocomplete-user').each(function() {
        let autocompleteUrl = '/user/find';
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