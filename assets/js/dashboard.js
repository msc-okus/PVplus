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


/*
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById("showGrid").addEventListener('click',function () {

            const coll = document.getElementsByClassName("showGridtoogle");
            const coll2 = document.getElementsByClassName("showLinetoogle");

            var t = document.getElementById("showGrid");

            if(t.value==="YES"){
                t.value="NO";
                document.getElementById("bton").className = "fa fa-bars";
                changeDisplay(coll,'block');
                changeDisplay(coll2,'none');
            }
            else if(t.value==="NO"){
                t.value="YES";
                document.getElementById("bton").className = "fa fa-pause";
                changeDisplay(coll,'none');
                changeDisplay(coll2,'block');
            }

        } );
    } );
*/

window.onload = function() {
    document.getElementById("clearButton").addEventListener("click", clear);
    document.getElementById("searchText").addEventListener("input", searchPlants);
    //console.log(document.getElementById("searchText"));
}

function changeDisplay(coll, value){
    for(let i=0, len=coll.length; i<len; i++) {
        coll[i].style["display"] = value;
    }
}

function clear(){
    document.getElementById("searchText").value = "";
    // Call seach, which should reset the result list
    searchPlants();
}

function searchPlants() {
    let input = document.getElementById("searchText");
    let filter = input.value.toLowerCase();
    let nodes = document.getElementsByClassName('target');

    for (let i = 0; i < nodes.length; i++) {
        if (nodes[i].innerText.toLowerCase().includes(filter)) {
            nodes[i].style.display = "block";
        } else {
            nodes[i].style.display = "none";
        }
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



