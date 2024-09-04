import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';

export default class extends Controller {
    static targets = ['plant', 'year', 'month', 'startday', 'endday', 'startbutton'];
    connect() {
        $(this.startbuttonTarget).attr('disabled', 'disabled');
        $(this.startdayTarget).attr('disabled', 'disabled');
        $(this.enddayTarget).attr('disabled', 'disabled');
        this.check();
    }

    check() {
        if (this.plantTarget.value !== '' && this.yearTarget.value > 2000 && this.monthTarget.value !== '') {
            $(this.startbuttonTarget).removeAttr('disabled');
            $(this.startdayTarget).removeAttr('disabled');
            $(this.enddayTarget).removeAttr('disabled');
            this.adjustStartDay();
            this.adjustEndDay(0);
        } else {
            $(this.startbuttonTarget).attr('disabled', 'disabled');
        }
    }

    checkStartDay(){
        this.adjustEndDay(this.startdayTarget.value);
    }

    checkEndDay(){

    }

    adjustStartDay() {
        let lastday = this.lastday(this.yearTarget.value, this.monthTarget.value);
        let oldOptions = this.startdayTarget.options;
        for(let i = oldOptions.length - 1; i >= 0; i--) {
            this.startdayTarget.remove(i);
        }

        for (let i = 0; i <= lastday; i++) {
            let option = document.createElement('option')
            if (i === 0) {
                option.text = 'Please choose a Start Day';
                option.value = 0;
            } else {
                option.text = i;
                option.value = i;
            }
            this.startdayTarget.add(option);
        }
    }

    adjustEndDay(from = 0) {
        let lastday = this.lastday(this.yearTarget.value, this.monthTarget.value);
        let oldOptions = this.enddayTarget.options;
        let oldValue = parseInt(this.enddayTarget.value);
        for (let i = oldOptions.length - 1; i >= 0; i--) {
            this.enddayTarget.remove(i);
        }

        for (let i = parseInt(from); i <= lastday; i++) {
            let option = document.createElement('option')
            if (i === 0) {
                option.text = 'Please choose a End Day';
                option.value = 0;
            } else {
                option.text = i;
                option.value = i;
            }
            this.enddayTarget.add(option);
        }
        if (oldValue >= this.startdayTarget.value) {
            this.enddayTarget.value = oldValue
        } else {
            this.enddayTarget.value = lastday
        }
    }

    // Create a new Date object representing the last day of the specified month
    lastday(y, m){
        return new Date(y, m, 0).getDate();
    }

}
