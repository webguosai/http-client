<?php
require_once '../vendor/autoload.php';


$client  = new \Webguosai\HttpClient();
$url     = 'http://waophp.com/upload';
$data    = [
    'file' => new \CURLFile('1.jpeg'),
];
$response = $client->post($url, $data);
//dump($response->httpStatus);
//dump($response->request);
echo($response->body);
dump($response->json());