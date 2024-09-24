<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;
use Firebase\JWT\JWT;
use Laminas\Math\Rand;

$jsonData = file_get_contents('config/walletconfig.json');
$credentials = json_decode($jsonData, true);

class WalletPass {
    private $credentials;
    private $client;
    private $baseUrl = 'https://walletobjects.googleapis.com/walletobjects/v1';
    private $issuerId = '3388000000022754147';
    private $classId;


    public function __construct($credentialsPath) {
        $this->credentials = json_decode(file_get_contents($credentialsPath), true);

        if ($this->credentials === null) {
            die('Fehler beim Laden der JSON-Datei: ' . json_last_error_msg());
        }

        $this->client = new Client();
        $this->classId = $this->issuerId . '.meins';
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

    public function createPassObject() {
        $len = 16; // Increased length for stronger randomness
        $randomBytes = random_bytes($len);
        $randomNumber = sprintf('%0' . $len . 'x', bin2hex($randomBytes));
      
        $objectId = "{$this->issuerId}.{$randomNumber}";
      
        // Assuming form data is already validated
        $school = $_POST['form_school'];
        $firstName = $_POST['form_firstName'];
        $lastName = $_POST['form_lastName'];

        $genericObject = [
            'id' => $objectId,
            'classId' => $this->classId,
            'logo' => [
                'sourceUri' => [
                    'uri' => 'https://raw.githubusercontent.com/PrakiRikeki/GoogleWallet/main/config/ribeka-sqare.png'
                ],
                'contentDescription' => [
                    'defaultValue' => [
                        'language' => 'en-US',
                        'value' => 'LOGO_IMAGE_DESCRIPTION'
                    ]
                ]
            ],
            'cardTitle' => [
                'defaultValue' => [
                    'language' => 'en-US',
                    'value' => 'Schülerausweis'
                ]
            ],
            'subheader' => [
                'defaultValue' => [
                    'language' => 'en-US',
                    'value' => $school
                ]
            ],
            'header' => [
                'defaultValue' => [
                    'language' => 'en-US',
                    'value' => $firstName . ' ' . $lastName
                ]
            ],
            'textModulesData' => [
                [
                    'id' => 'date',
                    'header' => 'Gültig bis',
                    'body' => '09/2024'
                ],
                [
                    'id' => 'adress',
                    'header' => 'Wohnort',
                    'body' => 'Musterstraße 12 12345 Neustadt'
                ],
                [
                    'id' => 'birth_date',
                    'header' => 'Geburtsdatum',
                    'body' => '31.07.2022'
                ],
            ],
            'barcode' => [
                'type' => 'EAN_13',
                'value' =>  978020137962,
                'alternateText' =>  978020137962
            ],
            'hexBackgroundColor' => '#4285f4',
            'heroImage' => [
                'sourceUri' => [
                    'uri' => 'https://raw.githubusercontent.com/PrakiRikeki/GoogleWallet/main/config/banner.png'
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


    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");

    $walletPass = new WalletPass('config/walletconfig.json');

    echo $walletPass->createPassObject();


?>



