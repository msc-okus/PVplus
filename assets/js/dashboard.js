import $ from "jquery";
import fdatepicker from 'foundation-datepicker';

function Display() {
    // Get the checkbox
    let checkBox = document.getElementById("exampleSwitch");

    // If the checkbox is checked, display the output text
    if (checkBox.checked === false) {
        document.getElementById('hour').innerText = "f";
    } else {
        document.getElementById('hour').innerText = "t";
    }
}


$(".js-submit-onchange").change(function () {
    $("#mysubmit").val('yes');
    $("#chart-control").submit();
});

$(".js-submit-onchange-select").change(function () {
    $("#mysubmit").val('select');
    $("#chart-control").submit();
});

$('#startDate').fdatepicker({
    language: 'en',
    weekStart: '1',
    // endDate: dateString,
});



