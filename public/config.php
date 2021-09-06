<?php

const DB_SYSTEM_DATA = "pvp_data";

$GLOBALS['StartEndTimesAlert'] = [
    '01' => ['start' => '11', 'end' => '15'],
    '02' => ['start' => '11', 'end' => '15'],
    '03' => ['start' => '10', 'end' => '16'],
    '04' => ['start' => '10', 'end' => '16'],
    '05' => ['start' => '09', 'end' => '18'],
    '06' => ['start' => '09', 'end' => '18'],
    '07' => ['start' => '09', 'end' => '18'],
    '08' => ['start' => '09', 'end' => '18'],
    '09' => ['start' => '10', 'end' => '17'],
    '10' => ['start' => '10', 'end' => '16'],
    '11' => ['start' => '11', 'end' => '15'],
    '12' => ['start' => '11', 'end' => '15'],
];

$GLOBALS['maxMessages'] = 7;

$GLOBALS['abweichung']['io'] = [
    'normal'    => 1 * 3600, // bis 1 Stunde
    'warning'   => 2 * 3600, // bis 2 Stunden
    'alert'     => 2 * 3600, // ab mehr als 2 Stunden
];
$GLOBALS['abweichung']['produktion'] = [
    'green' => 0, 'normal' => 0,
    'yellow' => -15, 'warning' => 15,
    'red'    => -20, 'alert'    => 20,
];

// abweichung auf Inverter Ebene
$GLOBALS['abweichung']['inverter']['string'] = [
    'normal'    => 0,
    'warning'   => 50,  // ziel 30 nur für dev auf anderen Wert stellen
    'alert'     => 80,  // ziel 50 nur für dev auf anderen Wert stellen
];
$GLOBALS['abweichung']['inverter']['zwr'] = [
    'normal'    => 0,
    'warning'   => 50,  // Werte validieren sind nur aus dem Bauch gewählt
    'alert'     => 80,  // Werte validieren sind nur aus dem Bauch gewählt
];

// abweichung auf String Ebene

$GLOBALS['abweichung']['string']['string'] = [
    'normal'    => 0,
    'warning'   => 50,  // ziel 30 nur für dev auf anderen Wert stellen
    'alert'     => 80,  // ziel 50 nur für dev auf anderen Wert stellen
];
$GLOBALS['abweichung']['string']['zwr'] = [
    'normal'    => 0,
    'warning'   => 50,  // ziel 30 nur für dev auf anderen Wert stellen
    'alert'     => 80,  // ziel 50 nur für dev auf anderen Wert stellen
];
