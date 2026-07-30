<?php

require "../config/config.php";

if (!isset($_GET['shop']) || !isset($_GET['code'])) {
    die("Missing required parameters.");
}

$shop = $_GET['shop'];
$code = $_GET['code'];

$url = "https://{$shop}/admin/oauth/access_token";

$data = [
    "client_id" => SHOPIFY_CLIENT_ID,
    "client_secret" => SHOPIFY_CLIENT_SECRET,
    "code" => $code
];

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Accept: application/json"
    ]
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    die("cURL Error: " . curl_error($ch));
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

echo "<h2>HTTP Status: {$httpCode}</h2>";

echo "<pre>";
print_r(json_decode($response, true));
echo "</pre>";