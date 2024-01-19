import $ from "jquery";
import 'autocomplete.js/dist/autocomplete.jquery';

$(document).ready(function() {
    $('.js-autocomplete-plant').each(function() {
        let autocompleteUrl = '/anlagen/find';
        $(this).autocomplete({hint: false}, [
            {
                source: function(query, cb) {
                    $.ajax({
                        url: autocompleteUrl+'?query='+query
                    }).then(function(data) {
                        cb(data.anlagen);
                    });
                },
                displayKey: 'plant_name',
                debounce: 200 // only request every 200 milliesecond
            }
        ]);
    });
});