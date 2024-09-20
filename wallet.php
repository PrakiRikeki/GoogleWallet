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

    private function formatCodabar($accountNumber) {
        return 'A' . $accountNumber . 'A';
    }

    public function createPassObject() {
        $len = 4;
        $randomBytes = Rand::getBytes($len);
        $randomNumber = hexdec(bin2hex($randomBytes));

        $randomNumber = 1000 + ($randomNumber % 9000);      
        $objectId = "{$this->issuerId}.{$randomNumber}"; 

        $form_school = htmlspecialchars($_POST['form_school']);
        $form_firstName = htmlspecialchars($_POST['form_firstName']);
        $form_lastName = htmlspecialchars($_POST['form_lastName']);

        $school = $randomNumber;
        $codabar = $this->formatCodabar($accountNumber);
        $firstName = $form_firstName;
        $lastName = $form_lastName;
        

        $genericObject = [
            'id' => $objectId,
            'classId' => $this->classId,
            'genericType' => 'GENERIC_TYPE_UNSPECIFIED',
            'url' => 'https://google.com',
            'logo' => [
                'sourceUri' => [
                    'uri' => 'https://www.alleycat.org/wp-content/uploads/2019/03/FELV-cat.jpg'
                ]
            ],
            'cardTitle' => [
                'defaultValue' => [
                    'language' => 'en',
                    'value' => 'hgfgfhfh'
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
                'alternateText' => $accountNumber
            ],
            'hexBackgroundColor' => '#6e3acf',
            'heroImage' => [
                'sourceUri' => [
                    'uri' => 'https://www.alleycat.org/wp-content/uploads/2019/03/FELV-cat.jpg'
                ],
                'contentDescription' => [
                    'defaultValue' => [
                        'language' => 'en-US',
                        'value' => 'HERO_IMAGE_DESCRIPTION'
                    ]
                ]
            ]
        ];

        $genericObject = [
            'id' => $objectId,
            'classId' => $this->classId,
            'logo' => [
                'sourceUri' => [
                    'uri' => 'https://storage.googleapis.com/wallet-lab-tools-codelab-artifacts-public/pass_google_logo.jpg'
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
                    'value' => 'Schülerausweiß'
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
                    'id' => 'points',
                    'header' => 'POINTS',
                    'body' => '1112'
                ],
                [
                    'id' => 'contacts',
                    'header' => 'CONTACTS',
                    'body' => '79'
                ]
            ],
            'barcode' => [
                'type' => 'QR_CODE',
                'value' => 'BARCODE_VALUE',
                'alternateText' => ''
            ],
            'hexBackgroundColor' => '#4285f4',
            'heroImage' => [
                'sourceUri' => [
                    'uri' => 'https://storage.googleapis.com/wallet-lab-tools-codelab-artifacts-public/google-io-hero-demo-only.png'
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



