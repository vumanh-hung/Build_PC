<?php

/**
 * Google OAuth Configuration
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

// Thông tin OAuth từ Google Cloud Console
define('GOOGLE_CLIENT_ID', '333676079158-nastgg7k2so4iccd8g7jlr4f1ka90rb6.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_REDIRECT_URI', SITE_URL . '/page/google_callback.php');

// Khởi tạo Google Client
function getGoogleClient()
{
    $client = new Google_Client();
    $client->setClientId(GOOGLE_CLIENT_ID);
    $client->setClientSecret(GOOGLE_CLIENT_SECRET);
    $client->setRedirectUri(GOOGLE_REDIRECT_URI);
    $client->addScope("email");
    $client->addScope("profile");
    $client->addScope("https://www.googleapis.com/auth/userinfo.profile");

    return $client;
}
