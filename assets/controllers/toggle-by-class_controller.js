import { Controller } from 'stimulus';

export default class extends Controller {
    static values = {
        action: String
    }
    connect() {
        if(this.actionValue === 'togglePaNull'){
            this.togglePaNull();
        }
        if(this.actionValue === 'togglePaOne'){
            this.togglePaOne();
        }
        if(this.actionValue === 'togglePaTwo'){
            this.togglePaTwo();
        }
        if(this.actionValue === 'togglePaThree'){
            this.togglePaThree();
        }
    }

    toggle(){
        const counterNumberElement = document.getElementsByClassName('js-pa-0');
        //counterNumberElement[1].classList.add('hidden');

        let fLen = counterNumberElement.length;
        for (let i = 0; i < fLen; i++) {
            counterNumberElement[i].classList.toggle('hidden');
        }
    }

    togglePaNull(){
        const counterNumberElement = document.getElementsByClassName('js-pa-0');
        let fLen = counterNumberElement.length;
        for (let i = 0; i < fLen; i++) {
            counterNumberElement[i].classList.toggle('hidden');
        }
    }

    togglePaOne(){
        const counterNumberElement = document.getElementsByClassName('js-pa-1');
        let fLen = counterNumberElement.length;
        for (let i = 0; i < fLen; i++) {
            counterNumberElement[i].classList.toggle('hidden');
        }
    }

    togglePaTwo(){
        const counterNumberElement = document.getElementsByClassName('js-pa-2');
        let fLen = counterNumberElement.length;
        for (let i = 0; i < fLen; i++) {
            counterNumberElement[i].classList.toggle('hidden');
        }
    }

    togglePaThree(){
        const counterNumberElement = document.getElementsByClassName('js-pa-3');
        let fLen = counterNumberElement.length;
        for (let i = 0; i < fLen; i++) {
            counterNumberElement[i].classList.toggle('hidden');
        }
    }


}
