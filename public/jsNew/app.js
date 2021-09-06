$(document).foundation();

$('.my-alert-box').closest('[data-alert]').fadeOut(3000);

if ($('#uhr').length > 0) {
    window.onload = ticken;
}

function ticken(){
    let stunden, minuten, sekunden;
    let StundenZahl, MinutenZahl, SekundenZahl;
    let heute;

    heute = new Date();
    StundenZahl = heute.getUTCHours() + 2;
    MinutenZahl = heute.getMinutes();

    stunden = StundenZahl + ":";
    if (MinutenZahl < 10) {minuten = "0" + MinutenZahl;}
    else {minuten = MinutenZahl;}
    zeit = stunden + minuten;
    uhr.innerHTML = zeit;

    window.setTimeout("ticken();", 10000);
}
