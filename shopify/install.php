<?php

require "../config/config.php";

$url = "https://" . SHOP . "/admin/oauth/authorize?" . http_build_query([
    "client_id" => SHOPIFY_CLIENT_ID,
    "scope" => SCOPES,
    "redirect_uri" => REDIRECT_URI
]);

header("Location: ".$url);
exit;