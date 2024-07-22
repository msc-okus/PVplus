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

    static function getSystemKeys($mcUser, $mcPassword, $mcToken, $curl = null) {
        $oauthThoken = self::auth($mcUser, $mcPassword, $mcToken, $curl);

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.meteocontrol.de/v2/systems",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                "X-API-KEY: ". $mcToken,
                "Cache-Control: no-cache",
                'Authorization: Bearer '.$oauthThoken['access_token'],
            ),
        ));

        $response = json_decode(curl_exec($curl), true, 512, JSON_THROW_ON_ERROR);

        if (curl_errno($curl)) {
            echo curl_error($curl);
        }

        return $response['data'];
    }

    static function getSystemKeySingleMeaserment($mcUser, $mcPassword, $mcToken, $key, $device, $abbrevationId, $from = 0, $to = 0, $resolution = "fifteen-minutes", $curl = null)
    {
        if (is_int($from) && is_int($to)) {
            $from = urlencode(date('c', $from - 900));
            $to = urlencode(date('c', $to));
            #echo "CURLOPT_URL => https://api.meteocontrol.de/v2/systems/$key/sensors/$device/abbreviations/$abbrevationId/measurements?from=$from&to=$to&resolution=$resolution\n";


            $oauthThoken = self::auth($mcUser, $mcPassword, $mcToken, $curl);
            curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://api.meteocontrol.de/v2/systems/$key/sensors/$device/abbreviations/$abbrevationId/measurements?from=$from&to=$to&resolution=$resolution",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        "X-API-KEY: ". $mcToken,
                        "Cache-Control: no-cache",
                        'Authorization: Bearer '.$oauthThoken['access_token'],
                    ),
                )
            );

            $response = json_decode(curl_exec($curl), true, 512, JSON_THROW_ON_ERROR);
            curl_close($curl);

            return $response;
        }
    }

    static function getSystemKeyMeaserment($mcUser, $mcPassword, $mcToken, $key, $type, $from = 0, $to = 0, $resolution = "fifteen-minutes", $curl = null)
    {
        if (is_int($from) && is_int($to)) {
            $from = urlencode(date('c', $from - 900));
            $to = urlencode(date('c', $to));
            #echo "CURLOPT_URL => https://api.meteocontrol.de/v2/systems/$key/$type/bulk/measurements?from=$from&to=$to&resolution=$resolution\n";

            $oauthThoken = self::auth($mcUser, $mcPassword, $mcToken, $curl);

            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://api.meteocontrol.de/v2/systems/$key/$type/bulk/measurements?from=$from&to=$to&resolution=$resolution",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        "X-API-KEY: ". $mcToken,
                        "Cache-Control: no-cache",
                        'Authorization: Bearer '.$oauthThoken['access_token'],
                    ),
                )
            );

            $response = json_decode(curl_exec($curl), true, 512, JSON_THROW_ON_ERROR);

            if (curl_errno($curl)) {
                echo curl_error($curl);
            }

            return $response;
        }
    }


    /**
     * @throws Exception
     */
    public function getSystemsKeyBulkMeaserments($mcUser, $mcPassword, $mcToken, $key, int $from = 0, int $to = 0, $resolution = "fifteen-minutes", $timeZonePlant = "Europe/Berlin", $curl = NULL)
    {
        if (is_int($from) && is_int($to)) {
            $offsetServerUTC = new \DateTimeZone("UTC");
            $offsetServer = new \DateTimeZone("Europe/Berlin");

            $plantoffset = new \DateTimeZone($timeZonePlant);
            $totalOffset = $plantoffset->getOffset(new \DateTime("now")) - $offsetServerUTC->getOffset(new \DateTime("now")) - $offsetServer->getOffset(new \DateTime("now"));
            #echo $timeZonePlant;
            date_default_timezone_set('Asia/Qyzylorda');
            $timestamp = time();
            $datum = date("d.m.Y",$timestamp);
            $uhrzeit = date("H:i",$timestamp);
            echo $datum," - ",$uhrzeit," Uhr";
            exit;

            $from = urlencode(date('c', $from - 900)); // minus 14 Minute, API liefert seit mitte April wenn ich Daten für 5:00 Uhr abfrage erst daten ab 5:15, wenn ich 4:46 abfrage bekomme ich die Daten von 5:00
            $to = urlencode(date('c', $to));
            #dump($timeZonePlant, "https://api.meteocontrol.de/v2/systems/$key/bulk/measurements?from=$from&to=$to&resolution=$resolution");
            $oauthThoken = self::auth($mcUser, $mcPassword, $mcToken, $curl);

            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://api.meteocontrol.de/v2/systems/$key/bulk/measurements?from=$from&to=$to&resolution=$resolution",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => [
                    "X-API-KEY: ". $mcToken,
                    "Cache-Control: no-cache",
                    'Authorization: Bearer '.$oauthThoken['access_token'],
                ],
            ]);

            $json = curl_exec($curl);
            try {
                $response = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            } catch(Exception $e) {
                $response = false;
            }


            if (curl_errno($curl)) {
                echo curl_error($curl);
            }


            return $response;
        }

        return false;
    }

    /**
     * @throws \JsonException
     */
    static function getSystemsKeySensorsMeaserments($mcUser, $mcPassword, $mcToken, $key, $from = 0, $to = 0, $resolution = "fifteen-minutes", $curl = null) {
        if (is_int($from) && is_int($to)) {
            $from = urlencode(date('c', $from - 900)); // minus 14 Minute, API liefert seit mitte April wenn ich Daten für 5:00 Uhr abfrage erst daten ab 5:15, wenn ich 4:46 abfrage bekomme ich die Daten von 5:00
            $to = urlencode(date('c', $to));
            $oauthThoken = self::auth($mcUser, $mcPassword, $mcToken, $curl);
            curl_setopt_array($curl, [
                    CURLOPT_URL => "https://api.meteocontrol.de/v2/systems/$key/sensors/bulk/measurements?from=$from&to=$to&resolution=$resolution",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => [
                        "X-API-KEY: ". $mcToken,
                        "Cache-Control: no-cache",
                        'Authorization: Bearer '.$oauthThoken['access_token'],
                    ],
                ]
            );

            $json = curl_exec($curl);
            try {
                $response = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            } catch(Exception $e) {
                $response = false;
            }

            return $response;
        }

        return false;
    }

    static function getSystemsKeyBasicsMeaserments($mcUser, $mcPassword, $mcToken, $key, $from = 0, $to = 0, $resolution = "fifteen-minutes", $curl = null) {
        if (is_int($from) && is_int($to)) {
            $from = urlencode(date('c', $from - 900));
            $to = urlencode(date('c', $to));
            //echo "CURLOPT_URL => https://api.meteocontrol.de/v2/systems/$key/basics/bulk/measurements?from=$from&to=$to&resolution=$resolution\n";


            $oauthThoken = self::auth($mcUser, $mcPassword, $mcToken, $curl);
            curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://api.meteocontrol.de/v2/systems/$key/basics/bulk/measurements?from=$from&to=$to&resolution=$resolution",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        "X-API-KEY: ". $mcToken,
                        "Cache-Control: no-cache",
                        'Authorization: Bearer '.$oauthThoken['access_token'],
                    ),
                )
            );

            $json = curl_exec($curl);
            try {
                $response = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            } catch(Exception $e) {
                $response = false;
            }

            return $response;
        }

        return false;
    }

    static function getSystemsKeyInverters($mcUser, $mcPassword, $mcToken, $key, $curl = null) {
        //echo "CURLOPT_URL => https://api.meteocontrol.de/v2/systems/$key/inverters\n";

        $oauthThoken = self::auth($mcUser, $mcPassword, $mcToken, $curl);

        curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.meteocontrol.de/v2/systems/$key/inverters",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    "X-API-KEY: ". $mcToken,
                    "Cache-Control: no-cache",
                    'Authorization: Bearer '.$oauthThoken['access_token'],
                ),
            )
        );

        $json = curl_exec($curl);
        try {
            $response = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch(Exception $e) {
            $response = false;
        }

        return $response;
    }

    /**
     * Get an authentication token
     */
static function auth($mcUser, $mcPassword, $mcToken, $curl = null)
    {

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.meteocontrol.de/v2/login",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST =>false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "grant_type=password&client_id=vcom-api&client_secret=AYB=~9_f-BvNoLt8+x=3maCq)>/?@Nom&username=$mcUser&password=$mcPassword",
            CURLOPT_HTTPHEADER => array(
                "content-type: application/x-www-form-urlencoded",
                "X-API-KEY: ". $mcToken,
            ),
        ));

        $response = curl_exec($curl);

        return json_decode($response, true);
    }
}

