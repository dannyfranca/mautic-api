<?php

define('MAUTIC_API', [
    'baseUrl' => 'https://your-mautic.com', //Mautic URL
    'version' => 'basic', //auth type (basic, OAuth1a, OAuth2)
    //basic auth
    'userName' => '',
    'password' => '',
    //OAuth (Not Stable)
    'clientKey' => '',
    'clientSecret' => '',
    'callback' => '',
    'tokenFileName' => 'token.json' //If you want to save token info in a json file, set the name you want (not recommended, use database instead)
]);

require __DIR__ . '/vendor/autoload.php';