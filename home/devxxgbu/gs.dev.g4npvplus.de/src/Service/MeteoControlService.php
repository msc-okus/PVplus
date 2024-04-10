<?php
namespace App\Service;


use Exception;

/**
 * Class MeteoControl
 */
class MeteoControlService
{
    static function checkBulkResponse($bulk): bool
    {

        return false;
    }

    static function getSystemKeys($mcUser, $mcPassword, $mcToken, ) {

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_USERPWD, $mcUser);
        curl_setopt($curl, CURLOPT_PASSWORD, $mcPassword);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.meteocontrol.de/v2/systems",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "X-API-KEY: " . $mcToken,
                "Cache-Control: no-cache"
            ],
        ]);

        $response_curl = curl_exec($curl);
        #echo($response_curl);
        $response = json_decode($response_curl, true, 512, JSON_THROW_ON_ERROR);
        #echo $response;
        #print_r($response);
        curl_close($curl);


        return $response['data'];
    }

    static function getSystemKeySingleMeaserment($mcUser, $mcPassword, $mcToken, $key, $device, $abbrevationId, $from = 0, $to = 0, $resolution = "fifteen-minutes")
    {
        if (is_int($from) && is_int($to)) {
            if($timeZonePlant != 'Europe/Berlin') {
                $offsetServerUTC = new \DateTimeZone("UTC");
                $offsetServer = new \DateTimeZone("Europe/Berlin");
                $plantoffset = new \DateTimeZone($timeZonePlant);
                $totalOffset = $plantoffset->getOffset(new \DateTime("now")) - $offsetServerUTC->getOffset(new \DateTime("now")) - $offsetServer->getOffset(new \DateTime("now"));

                date_default_timezone_set($timeZonePlant);
                $from = urlencode(date('c', ($from) - 900)); // minus 14 Minute, API liefert seit mitte April wenn ich Daten für 5:00 Uhr abfrage erst daten ab 5:15, wenn ich 4:46 abfrage bekomme ich die Daten von 5:00
                $to = urlencode(date('c', $to));
            }else{
                $from = urlencode(date('c', $from - 900)); // minus 14 Minute, API liefert seit mitte April wenn ich Daten für 5:00 Uhr abfrage erst daten ab 5:15, wenn ich 4:46 abfrage bekomme ich die Daten von 5:00
                $to = urlencode(date('c', $to));
            }

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_USERPWD, $mcUser);
            curl_setopt($curl, CURLOPT_PASSWORD, $mcPassword);
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt_array($curl, [CURLOPT_URL => "https://api.meteocontrol.de/v2/systems/$key/sensors/$device/abbreviations/$abbrevationId/measurements?from=$from&to=$to&resolution=$resolution", CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "GET", CURLOPT_HTTPHEADER => [
                "X-API-KEY: ". $mcToken,
                "Cache-Control: no-cache"
            ]]);

            $response = json_decode(curl_exec($curl), true, 512, JSON_THROW_ON_ERROR);
            curl_close($curl);

            return $response;
        }
    }

    static function getSystemKeyMeaserment($mcUser, $mcPassword, $mcToken, $key, $type, $from = 0, $to = 0, $resolution = "fifteen-minutes")
    {
        if (is_int($from) && is_int($to)) {
            $from = urlencode(date('c', $from - 900));
            $to = urlencode(date('c', $to));
            //echo "CURLOPT_URL => https://api.meteocontrol.de/v2/systems/$key/$type/bulk/measurements?from=$from&to=$to&resolution=$resolution\n";

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_USERPWD, $mcUser);
            curl_setopt($curl, CURLOPT_PASSWORD, $mcPassword);
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt_array($curl, [CURLOPT_URL => "https://api.meteocontrol.de/v2/systems/$key/$type/bulk/measurements?from=$from&to=$to&resolution=$resolution", CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "GET", CURLOPT_HTTPHEADER => [
                "X-API-KEY: ". $mcToken,
                "Cache-Control: no-cache"
            ]]);

            $response = json_decode(curl_exec($curl), true, 512, JSON_THROW_ON_ERROR);
            curl_close($curl);

            return $response;
        }
    }

    public function getSystemsKeyBulkMeaserments($mcUser, $mcPassword, $mcToken, $key, int $from = 0, int $to = 0, $resolution = "fifteen-minutes", $timeZonePlant = "Europe/Berlin", $curl = NULL) {
        if (is_int($from) && is_int($to)) {
            $offsetServerUTC = new \DateTimeZone("UTC");
            $offsetServer = new \DateTimeZone("Europe/Berlin");

            $plantoffset = new \DateTimeZone($timeZonePlant);
            $totalOffset = $plantoffset->getOffset(new \DateTime("now")) - $offsetServerUTC->getOffset(new \DateTime("now")) - $offsetServer->getOffset(new \DateTime("now"));

            date_default_timezone_set($timeZonePlant);
            $from = urlencode(date('c', ($from-$totalOffset) - 900)); // minus 14 Minute, API liefert seit mitte April wenn ich Daten für 5:00 Uhr abfrage erst daten ab 5:15, wenn ich 4:46 abfrage bekomme ich die Daten von 5:00
            $to = urlencode(date('c', $to-$totalOffset));

            curl_setopt($curl, CURLOPT_USERPWD, $mcUser);
            curl_setopt($curl, CURLOPT_PASSWORD, $mcPassword);
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt_array($curl, [CURLOPT_URL => "https://api.meteocontrol.de/v2/systems/$key/bulk/measurements?from=$from&to=$to&resolution=$resolution", CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "GET", CURLOPT_HTTPHEADER => [
                "X-API-KEY: ". $mcToken,
                "Cache-Control: no-cache"
            ]]);

            $response = json_decode(curl_exec($curl), true, 512, JSON_THROW_ON_ERROR);

            if (curl_errno($curl)) {
                echo curl_error($curl);
            }


            return $response;
        }

        return false;
    }

    static function getSystemsKeySensorsMeaserments($mcUser, $mcPassword, $mcToken, $key, $from = 0, $to = 0, $resolution = "fifteen-minutes") {
        if (is_int($from) && is_int($to)) {
            $from = urlencode(date('c', $from - 900)); // minus 14 Minute, API liefert seit mitte April wenn ich Daten für 5:00 Uhr abfrage erst daten ab 5:15, wenn ich 4:46 abfrage bekomme ich die Daten von 5:00
            $to = urlencode(date('c', $to));

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_USERPWD, $mcUser);
            curl_setopt($curl, CURLOPT_PASSWORD, $mcPassword);
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt_array($curl, [CURLOPT_URL => "https://api.meteocontrol.de/v2/systems/$key/sensors/bulk/measurements?from=$from&to=$to&resolution=$resolution", CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "GET", CURLOPT_HTTPHEADER => [
                "X-API-KEY: ". $mcToken,
                "Cache-Control: no-cache"
            ]]);

            $response = json_decode(curl_exec($curl), true, 512, JSON_THROW_ON_ERROR);
            curl_close($curl);
            return $response;
        }

        return false;
    }

    static function getSystemsKeyBasicsMeaserments($mcUser, $mcPassword, $mcToken, $key, $from = 0, $to = 0, $resolution = "fifteen-minutes") {
        if (is_int($from) && is_int($to)) {
            $from = urlencode(date('c', $from - 900));
            $to = urlencode(date('c', $to));
            //echo "CURLOPT_URL => https://api.meteocontrol.de/v2/systems/$key/basics/bulk/measurements?from=$from&to=$to&resolution=$resolution\n";

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_USERPWD, $mcUser);
            curl_setopt($curl, CURLOPT_PASSWORD, $mcPassword);
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt_array($curl, [CURLOPT_URL => "https://api.meteocontrol.de/v2/systems/$key/basics/bulk/measurements?from=$from&to=$to&resolution=$resolution", CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "GET", CURLOPT_HTTPHEADER => [
                "X-API-KEY: ". $mcToken,
                "Cache-Control: no-cache"
            ]]);

            $response = json_decode(curl_exec($curl), true, 512, JSON_THROW_ON_ERROR);
            curl_close($curl);

            return $response;
        }

        return false;
    }

    static function getSystemsKeyInverters($mcUser, $mcPassword, $mcToken, $key) {

        //echo "CURLOPT_URL => https://api.meteocontrol.de/v2/systems/$key/inverters\n";

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_USERPWD, $mcUser);
        curl_setopt($curl, CURLOPT_PASSWORD, $mcPassword);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt_array($curl, [CURLOPT_URL => "https://api.meteocontrol.de/v2/systems/$key/inverters", CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "GET", CURLOPT_HTTPHEADER => [
            "X-API-KEY: ". $mcToken,
            "Cache-Control: no-cache"
        ]]);

        $response = json_decode(curl_exec($curl), true, 512, JSON_THROW_ON_ERROR);
        curl_close($curl);

        return $response;
    }
}