<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;
use Firebase\JWT\JWT;

class WalletPass {
    private $credentials;
    private $client;
    private $baseUrl = 'https://walletobjects.googleapis.com/walletobjects/v1';
    private $issuerId = '';
    private $classId;

    public function __construct($credentialsPath) {
        $this->credentials = json_decode(file_get_contents($credentialsPath), true);
        $this->client = new Client();
        $this->classId = $this->issuerId . '.';
    }

    private function getAuthToken() {
        $tokenUri = 'https://oauth2.googleapis.com/token';

        $jwt = JWT::encode([
            'iss' => $this->credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/wallet_object.issuer',
            'aud' => $tokenUri,
            'exp' => time() + 3600,
            'iat' => time()
        ], $this->credentials['private_key'], 'RS256');

        $response = $this->client->post($tokenUri, [
            'form_params' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['access_token'];
    }

    private function formatCodabar($accountNumber) {
        return 'A' . $accountNumber . 'A';
    }

    public function createPassObject() {
        $randomNumber = rand(1000, 9999);
        $objectId = "{$this->issuerId}.{$randomNumber}";

        $accountNumber = "29085006805780";  // Hardcoded account number
        $codabar = $this->formatCodabar($accountNumber);
        $firstName = "Test ";
        $lastName = "Test";
        

        $genericObject = [
            'id' => $objectId,
            'classId' => $this->classId,
            'genericType' => 'GENERIC_TYPE_UNSPECIFIED',
            'logo' => [
                'sourceUri' => [
                    'uri' => ''
                ]
            ],
            'cardTitle' => [
                'defaultValue' => [
                    'language' => 'en',
                    'value' => ''
                ]
            ],
            'header' => [
                'defaultValue' => [
                    'language' => 'en',
                    'value' => $firstName . ' ' . $lastName
                ]
            ],
            'barcode' => [
                'type' => 'CODABAR',
                'value' => $codabar,
                'alternateText' => $accountNumber // Added missing 'alternateText'
            ],
            'hexBackgroundColor' => '#6e3acf', // Fixed formatting and added to array
            'heroImage' => [
                'sourceUri' => [
                    'uri' => ''
                ],
                'contentDescription' => [
                    'defaultValue' => [
                        'language' => 'en-US',
                        'value' => 'HERO_IMAGE_DESCRIPTION'
                    ]
                ]
            ]
        ];
        

        $claims = [
            'iss' => $this->credentials['client_email'],
            'aud' => 'google',
            'origins' => [],
            'typ' => 'savetowallet',
            'payload' => [
                'genericObjects' => [$genericObject]
            ]
        ];

        $token = JWT::encode($claims, $this->credentials['private_key'], 'RS256');
        $saveUrl = "https://pay.google.com/gp/v/save/{$token}";

        return $saveUrl;
    }
}


$json = json_decode(file_get_contents('prod.api.pvp.net/api/lol/euw/v1.1/game/by-summoner/20986461/recent?api_key=*key*'));

print_r($json);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");

    $walletPass = new WalletPass('config/walletconfig.json');

    echo $walletPass->createPassObject();
}


?>



