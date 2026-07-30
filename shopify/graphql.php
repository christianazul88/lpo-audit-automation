<?php

require_once "../config/config.php";

function shopifyGraphQL($query)
{
    $url = "https://" . SHOPIFY_STORE . "/admin/api/" . SHOPIFY_API_VERSION . "/graphql.json";

    $payload = json_encode([
        "query" => $query
    ]);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "X-Shopify-Access-Token: " . SHOPIFY_ACCESS_TOKEN
        ]
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        die(curl_error($ch));
    }

    curl_close($ch);

    return json_decode($response, true);
}