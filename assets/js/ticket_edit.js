import '../styles/components/autocomplete.scss';
import './components/autocomplete_plant';

export function display() {
    // Get the checkbox
    const checkBox = document.getElementById("exampleSwitch");
    // Get the output text
    const div = document.getElementById("midiv");

    // If the checkbox is checked, display the output text
    if (checkBox.checked === false){
        div.style.visibility = "hidden";
    } else {
        div.style.visibility = "visible";
    }
}