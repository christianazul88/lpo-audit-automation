<?php

require_once "../config/config.php";
require_once "graphql.php";

$url = "https://" . SHOPIFY_STORE .
       "/admin/api/" . SHOPIFY_API_VERSION .
       "/orders.json?status=any&limit=20";

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "X-Shopify-Access-Token: " . SHOPIFY_ACCESS_TOKEN,
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    die(curl_error($ch));
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

$data = json_decode($response, true);

if ($httpCode != 200) {
    echo "<pre>";
    print_r($data);
    exit;
}

echo "<style>
body{
    font-family:Arial;
    font-size:13px;
}
table{
    border-collapse:collapse;
    width:100%;
}
td,th{
    border:1px solid #ccc;
    padding:8px;
    vertical-align:top;
}
img{
    border:1px solid #ccc;
    margin-bottom:5px;
}
.comment{
    white-space:pre-wrap;
    font-size:12px;
    background:#fafafa;
    padding:6px;
}
</style>";

echo "<table>";

echo "<tr style='background:#efefef'>
<th>#</th>
<th>Shopify ID</th>
<th>Order #</th>
<th>Customer</th>
<th>Product</th>
<th>SKU</th>
<th>Qty</th>
<th>Unit Price</th>
<th>Discount</th>
<th>Sold Amount</th>
<th>Note</th>
<th>Comments</th>
<th>Proof of Payment</th>
<th>Financial</th>
<th>Fulfillment</th>
</tr>";

$num = 1;

foreach ($data['orders'] as $order) {

    //==========================
    // Customer
    //==========================

    if (!empty($order['customer'])) {

        $customer = trim(
            ($order['customer']['first_name'] ?? '') .
            ' ' .
            ($order['customer']['last_name'] ?? '')
        );

    } else {

        $customer = "No customer";

    }

    $note = $order['note'] ?? '';

    $financial = $order['financial_status'] ?? '';

    $fulfillment = $order['fulfillment_status'] ?? 'Unfulfilled';



    //======================================================
    // GET COMMENT EVENTS (ONLY ONCE PER ORDER)
    //======================================================

    $orderGraphQLId = "gid://shopify/Order/" . $order['id'];

    $query = <<<GRAPHQL
query{
  order(id:"$orderGraphQLId"){

    events(first:50){

      nodes{

        __typename

        ... on CommentEvent{

          id

          message

          attachments{

            id
            name
            url

          }

        }

      }

    }

  }

}
GRAPHQL;

    $graph = shopifyGraphQL($query);

    $comments = [];

    $images = [];

    if(isset($graph['data']['order']['events']['nodes'])){

        foreach($graph['data']['order']['events']['nodes'] as $event){

            if($event['__typename'] == "CommentEvent"){

                $comments[] = $event['message'];

                if(isset($event['attachments'])){

                    foreach($event['attachments'] as $attachment){

                        $images[] = $attachment;

                    }

                }

            }

        }

    }



    //==================================================
    // DISPLAY PRODUCTS
    //==================================================

    foreach ($order['line_items'] as $item) {

        $qty = $item['quantity'];

        $unitPrice = (float)$item['price'];

        $discount = (float)($item['total_discount'] ?? 0);

        $soldAmount = ($qty * $unitPrice) - $discount;

        echo "<tr>";

        echo "<td>".$num."</td>";

        echo "<td>".$order['id']."</td>";

        echo "<td>".$order['name']."</td>";

        echo "<td>".$customer."</td>";

        echo "<td>".$item['title']."</td>";

        echo "<td>".($item['sku'] ?? '')."</td>";

        echo "<td align='center'>".$qty."</td>";

        echo "<td align='right'>".number_format($unitPrice,2)."</td>";

        echo "<td align='right'>".number_format($discount,2)."</td>";

        echo "<td align='right'><b>".number_format($soldAmount,2)."</b></td>";

        echo "<td>".$note."</td>";



        //================ COMMENTS ====================

        echo "<td>";

        if(count($comments)){

            foreach($comments as $comment){

                echo "<div class='comment'>";

                echo nl2br(htmlspecialchars($comment));

                echo "</div><br>";

            }

        }else{

            echo "-";

        }

        echo "</td>";



        //================ IMAGES ====================

        echo "<td>";

        if(count($images)){

            foreach($images as $img){

                echo "<a href='".$img['url']."' target='_blank'>";

                echo "<img src='".$img['url']."' width='120'><br>";

                echo "</a>";

                echo htmlspecialchars($img['name']);

                echo "<br><br>";

            }

        }else{

            echo "-";

        }

        echo "</td>";



        echo "<td>".$financial."</td>";

        echo "<td>".$fulfillment."</td>";

        echo "</tr>";

        $num++;

    }

}

echo "</table>";