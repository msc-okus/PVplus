<?php
// src/Sewrvice/externalApisSewrvice.php
namespace App\Service;
use Symfony\Contracts\HttpClient\HttpClientInterface;
class externalApisService
{
    public function __construct(
        private HttpClientInterface $client,
    ) {
    }

    public function getAccessTokenMc($url, $mcUser ,$mcPassword, $mcToken)
    {

        $url = $url.'/login';

        $response = $this->client->request('GET', $url, [
            'headers' => [
                "content-type: application/x-www-form-urlencoded",
                "X-API-KEY: ". $mcToken,
            ],
            'body' => [
                'grant_type' => 'password',
                'client_id' => 'vcom-api',
                'client_secret' => 'AYB=~9_f-BvNoLt8+x=3maCq)>/?@Nom' ,
                'username' => $mcUser,
                'password' => $mcPassword
            ],

        ]);

print_r($response->toArray());
exit;

        return $response;
    }
}