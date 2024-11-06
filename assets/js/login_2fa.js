import 'foundation-sites';
import $ from 'jquery';

$(document).foundation();
$('#login').foundation('toggle');

$(document).foundation();


// Based on code from : https://codepen.io/groundtutorial/pen/OJwpXvW

//Initial references
const input = document.querySelectorAll(".input");
const inputField = document.querySelector(".authArea");
const submitButton = document.getElementById("submit");
const authCode = document.getElementById('_auth_code');

let inputCount = 0,
    numberOfInputFields = 6,
    maxInputFields = numberOfInputFields - 1,
    finalInput = "";

//Update input
const updateInputConfig = (element, disabledStatus) => {
    element.disabled = disabledStatus;
    if (!disabledStatus) {
        element.focus();
    } else {
        element.blur();
    }
};

input.forEach((element) => {
    element.addEventListener("keyup", (e) => {
        e.target.value = e.target.value.replace(/[^0-9]/g, "");
        let { value } = e.target;
        console.log(value.length);
        if (value.length === 1) {
            updateInputConfig(e.target, true);
            if (inputCount <= maxInputFields && e.key !== "Backspace") {
                finalInput += value;
                if (inputCount < maxInputFields) {
                    updateInputConfig(e.target.nextElementSibling, false);
                }
            }
            inputCount += 1;
        } else if (value.length === 0 && e.key === "Backspace") {
            finalInput = finalInput.substring(0, finalInput.length - 1);
            if (inputCount === 0) {
                updateInputConfig(e.target, false);
                return false;
            }
            updateInputConfig(e.target, true);
            e.target.previousElementSibling.value = "";
            updateInputConfig(e.target.previousElementSibling, false);
            inputCount -= 1;
        } else if (value.length > 1) {
            e.target.value = value.split("")[0];
        }
        submitButton.classList.add("hide");
    });
});

window.addEventListener("keyup", (e) => {
    if (inputCount > maxInputFields) {
        submitButton.classList.remove("hide");
        submitButton.classList.add("show");
        if (e.key === "Backspace") {
            finalInput = finalInput.substring(0, finalInput.length - 1);
            updateInputConfig(inputField.lastElementChild, false);
            inputField.lastElementChild.value = "";
            inputCount -= 1;
            submitButton.classList.add("hide");
        }
        authCode.value = finalInput;
    }
});


//Start
const startInput = () => {
    inputCount = 0;
    finalInput = "";
    input.forEach((element) => {
        element.value = "";
    });
    updateInputConfig(inputField.firstElementChild, false);
};


window.onload = startInput();