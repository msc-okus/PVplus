import '../styles/components/autocomplete.scss';
import './components/autocomplete_plant';
import './components/autocomplete_user';

export function CheckCheckboxes() {
    var array = [];
    const checkboxes = document.querySelectorAll('input[type=checkbox]:checked');

    for (var i = 0; i < checkboxes.length; i++) {
        array.push(checkboxes[i].value);
    }
    const jsonString = JSON.stringify(array);
    /*
    const xhr = new XMLHttpRequest();
    xhr.open("post", "{{ path('app_ticket_join') }}");
    xhr.setRequestHeader("Content-Type", "application/json")
    xhr.send(jsonString);
*/

        $.ajax({
        url: "/ticket/join",
        type: "POST",
        dataType: 'application/json',
        contentType: 'application/json; charset=utf-8',
        data: jsonString,
    });
}
