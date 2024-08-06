<?php
// src/Sewrvice/externalApisSewrvice.php
namespace App\Service;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\HttpClient;
use BenjaminFavre\OAuthHttpClient\OAuthHttpClient;
use BenjaminFavre\OAuthHttpClient\GrantType\ClientCredentialsGrantType;
class externalApisService
{
    public function __construct(
        private HttpClientInterface $client,
    ) {
    }

    public function getAccessTokenMc($url, $mcUser ,$mcPassword, $mcToken)
    {

        $url = $url.'/login';
        echo "$url, $mcUser ,$mcPassword, $mcToken";
        $httpClient = HttpClient::create();

        $grantType = new ClientCredentialsGrantType(
            $httpClient,
            $url, // The OAuth server token URL
            'vcom-api',
            'AYB=~9_f-BvNoLt8+x=3maCq)>/?@Nom'
        );

        $httpClient = new OAuthHttpClient($httpClient, $grantType);

        $response = $httpClient->request('POST', $url, [
            'headers' => [
                "X-API-KEY" => $mcToken,
                "Content-Type" => "application/x-www-form-urlencoded"
            ],
            'body' => [
                'grant_type' => 'client_credentials',
                'client_id' => 'vcom-api',
                'client_secret' => 'AYB=~9_f-BvNoLt8+x=3maCq)>/?@Nom' ,
                'username' => $mcUser,
                'password' => $mcPassword,
                'redirect_uri' => 'https://127.0.0.1:8000/connect',
            ],

        ]);

        print_r($response->getHeaders());

        exit;


        $response = $this->client->request('POST', $url, [
            'headers' => [
                "X-API-KEY" => $mcToken,
                "Content-Type" => "application/x-www-form-urlencoded",
                'Accept' => 'application/json'
            ],
            'body' => [
                'grant_type' => 'password',
                'client_id' => 'vcom-api',
                'client_secret' => 'AYB=~9_f-BvNoLt8+x=3maCq)>/?@Nom' ,
                'username' => $mcUser,
                'password' => $mcPassword,
                'redirect_uri' => 'https://127.0.0.1:8000/connect',
            ],

        ]);

print_r($response->toArray());
exit;

        return $response;
    }
}