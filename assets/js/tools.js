import $ from 'jquery';

function changeFunction() {
    var x = document.getElementById("tools_form_preselect").value;
    if (x === 'null' || x === '') {
    document.getElementById("f_1").style.visibility = "hidden";
    document.getElementById("f_2").style.visibility = "hidden";
}
    if (x === 'dbtools') {
    document.getElementById("thechange").innerHTML = "Tools: " + x;
    document.getElementById("f_1").style.visibility = "visible";
    document.getElementById("f_2").style.visibility = "hidden";
}
    if (x === 'dataload') {
    document.getElementById("thechange").innerHTML = "Tools: " + x;
    document.getElementById("f_1").style.visibility = "hidden";
    document.getElementById("f_2").style.visibility = "visible";
}
}

