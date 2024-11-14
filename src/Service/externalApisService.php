<?php
// src/Sewrvice/externalApisSewrvice.php
namespace App\Service;


class externalApisService
{
    public function __construct(
    ) {
    }

    public function getAccessToken($url, $postFileds, $headerFields, $apiType, $curlHeader)
    {


        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_SSL_VERIFYHOST =>false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>$postFileds,
            CURLOPT_HTTPHEADER => $headerFields,
            CURLOPT_HEADER => $curlHeader
        ));

        $body = curl_exec($curl);

        if($apiType == 'vcom'){
            curl_close($curl);
            $result = json_decode($body, true);
            $token = $result['access_token'];
        }

        if($apiType == 'huawai') {
            // extract header
            $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
            $header = substr($body, 0, $headerSize);
            $headers = $this->getHeaders($header);

            curl_close($curl);

            $token = $headers['xsrf-token'];
        }
        return $token;
    }

    public function getDataHuawai($url, $headerFields, $postFileds, $curlHeader, $resultParam)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_SSL_VERIFYHOST =>false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => $headerFields,
            CURLOPT_POSTFIELDS =>$postFileds,
            CURLOPT_HEADER => $curlHeader
        ));

        $body = curl_exec($curl);

        curl_close($curl);
        $result = json_decode($body, true, 1024);

        if($resultParam == 'stationCode'){
            $result = $result['data'][0]['stationCode'];
        }

        return $result;
    }

    public function getData($url, $headerFields)
    {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_SSL_VERIFYHOST =>false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => $headerFields
            ));

        $response = curl_exec($curl);

        curl_close($curl);
        $result = json_decode($response, true);

        return $result;

    }

    public function getHeaders($respHeaders) {
        $headers = [];

        $headerText = substr($respHeaders, 0, strpos($respHeaders, "\r\n\r\n"));

        foreach (explode("\r\n", $headerText) as $i => $line) {
            if ($i === 0) {
                $headers['http_code'] = $line;
            } else {
                list ($key, $value) = explode(': ', $line);

                $headers[$key] = $value;
            }
        }

        return $headers;
    }
}