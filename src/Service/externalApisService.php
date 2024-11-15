<?php
// src/Sewrvice/externalApisSewrvice.php
namespace App\Service;

class externalApisService
{
    public function __construct(
    ) {
    }

    public function getAccessToken(object $client, string $url, $postFileds, $headerFields, string $apiType)
    {
        $response = $client->request('POST', $url, [
            'headers' => $headerFields,
            'body' => $postFileds,
        ]);

        $headers = $response->getHeaders();

        $responseJson = $response->getContent();
        $responseData = json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR);

        if($apiType == 'vcom'){
            $responseJson = $response->getContent();
            $responseData = json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR);
            $token = $responseData['access_token'];
        }

        if($apiType == 'huawai') {
            $token = $headers['xsrf-token'][0];
        }
        return $token;
    }

    public function getDataHuawai($client, $url, $headerFields, $postFileds)
    {
        $response = $client->request('POST', $url, [
            'headers' => $headerFields,
            'body' => $postFileds,
        ]);

        $headers = $response->getHeaders();

        $responseJson = $response->getContent();
        $responseData = json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR);

        return $responseData;
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
}