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

$('#startDate').fdatepicker({
    language: 'en',
    weekStart: '1',
    // endDate: dateString,
});

$(document).ready(function () {
    $(".case5").on("change paste keyup", function () {
        let case5from = $("#case5from").val();
        let case5to = $("#case5to").val()
        let diff = ((case5to.substr(0, 2) * 60) + (case5to.substr(3, 2) * 1)) - ((case5from.substr(0, 2) * 60) + (case5from.substr(3, 2) * 1));
        if (case5from !== "" && case5to !== "" && $("#case5inverter").val() !== "" && $("#case5reason").val() !== "" && diff > 0) {
            $("#addCase5").removeAttr("disabled");
        } else {
            $("#addCase5").attr("disabled", true);
        }
    });

    $('.js-edit-case5').on("click", function () {
        let apiUrl = $(this).data('url');
        $.ajax({
            url: apiUrl
        }).then(function (data) {
            //console.log(data);
            let from = new Date(data.stampFrom);
            let to = new Date(data.stampTo)
            //console.log(to.getMinutes().toString().padStart(2, '0'));
            $("#case5id").val(data.id);
            $("#case5from").val(from.getHours().toString().padStart(2, '0') + ":" + from.getMinutes().toString().padStart(2, '0'));
            $("#case5to").val(to.getHours().toString().padStart(2, '0') + ":" + to.getMinutes().toString().padStart(2, '0'));
            $("#case5inverter").val(data.inverter);
            $("#case5reason").val(data.reason);
            $("#addCase5").removeAttr("disabled");
        });
    });

    $('.js-delete-case5').on("click", function () {
        let apiUrl = $(this).data('url');
        $.ajax({
            url: apiUrl
        }).then(function (data) {
            //console.log(apiUrl);
        });
        $(this).closest('.js-case5-item')
            .remove();
    });

});




