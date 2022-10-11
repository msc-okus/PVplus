import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'clock'
    ];
    static values = {
        interval: { default: 500, type: Number },
    };

    connect() {
        this._timer = setInterval(() => {
            this.ticken();
        }, 30000);

        this.ticken();
    }

    ticken(){
        let stunden, minuten, sekunden;
        let StundenZahl, MinutenZahl, SekundenZahl;
        let heute;
        let zeit;

        heute = new Date();
        StundenZahl = heute.getHours();
        MinutenZahl = heute.getMinutes();
        stunden = StundenZahl + ":";
        if (MinutenZahl < 10) {minuten = "0" + MinutenZahl;}
        else {minuten = MinutenZahl;}
        zeit = stunden + minuten;
        this.clockTarget.innerHTML = zeit;
    }
}
