export default function ticken(){
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
    uhr.innerHTML = zeit;

}