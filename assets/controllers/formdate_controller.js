import { Controller } from '@hotwired/stimulus';
import { useDispatch } from 'stimulus-use';
import { Reveal } from 'foundation-sites';
import $ from 'jquery';

export default class extends Controller {
    static targets =    ['switch'];

    static values = {
        formUrl: String,
    }
    connect() {
        $('#year').prop('disabled', 'disabled');
        $('#month').prop('disabled', 'disabled');
        $('#start-day').prop('disabled', 'disabled');
        $('#end-day').prop('disabled', 'disabled');
        $('#sendIt').prop('disabled', 'disabled');
        useDispatch(this);
    }

    enableYear(){
        $('#year').prop('disabled', false);
    }

    enableMonth(){
        $('#month').prop('disabled', false);
    }

    enableStartday(){
        $('#start-day').prop('disabled', false);
    }

    enableEndday(){
        $('#end-day').prop('disabled', false);
        let byId = (id) => document.getElementById(id);
        let startDay= byId("start-day").value;
        let monthSelect = byId("month");
        let yearSelect = byId("year");
        document.getElementById("end-day").options.length = 0;
        let daysInMonth = this.daysInMonth(monthSelect.value-1, yearSelect.value);

        for (var i = startDay; i <= daysInMonth; i++) {
            this.addOption(i, 1);
        }

        this.enableSendIt();
    }

    enableSendIt(){
        $('#sendIt').prop('disabled', false);
    }

    enableButtons(){
        $('#recalc-PA').prop('disabled', false);
        $('#new-report').prop('disabled', false);
    }

    changeDate(){
        let byId = (id) => document.getElementById(id);
        let monthSelect = byId("month");
        let yearSelect = byId("year");
        let daysInMonth = this.daysInMonth(monthSelect.value-1, yearSelect.value);

        let output = document.getElementById('output');

        this.remooveOptions();
        for (var i = 1; i <= daysInMonth; i++) {
            this.addOption(i, 0);
        }
        this.enableStartday();
    }

    daysInMonth (month, year) {
        return new Date(parseInt(year), parseInt(month) + 1, 0).getDate();
    }

    remooveOptions(){
        document.getElementById("start-day").options.length = 1;
        document.getElementById("end-day").options.length = 1;
    }

    addOption(i, endDayOnly) {
        let optionText = i;
        let optionValue = i;
        let optionHTML = `
            <option value="${optionValue}"> 
                ${optionText} 
            </option>`;
        if(endDayOnly == 0){
            $('#start-day').append(optionHTML);
        }

        $('#end-day').append(optionHTML);
    }

}
