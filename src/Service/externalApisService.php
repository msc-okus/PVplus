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

    public function getData($client, $url, $headerFields)
    {
        $response = $client->request('GET', $url, [
            'headers' => $headerFields
        ]);

        $responseJson = $response->getContent();
        $responseData = json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR);

        return $responseData;

    }
}