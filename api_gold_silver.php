<?php
// API endpoint untuk mengambil harga emas dan perak terkini
// Usage: /api_gold_silver.php atau /api_gold_silver.php?callback=functionName (untuk JSONP)

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once('config.php');
require_once('fungsi.php');

// Get latest price data
$price_data = db_row("SELECT * FROM `sa_gold_silver_price` ORDER BY `price_date` DESC LIMIT 1");

if ($price_data) {
    $response = array(
        'success' => true,
        'data' => array(
            'updatedAt' => date('d/m/Y H:i', strtotime($price_data['updated_at'])),
            'date' => $price_data['price_date'],
            'gold' => array(
                'sell' => 'Rp ' . number_format($price_data['gold_sell'], 0, ',', '.'),
                'buy' => 'Rp ' . number_format($price_data['gold_buy'], 0, ',', '.')
            ),
            'silver' => array(
                'sell' => 'Rp ' . number_format($price_data['silver_sell'], 0, ',', '.'),
                'buy' => 'Rp ' . number_format($price_data['silver_buy'], 0, ',', '.')
            )
        )
    );
} else {
    $response = array(
        'success' => false,
        'message' => 'No price data available',
        'data' => array(
            'updatedAt' => date('d/m/Y H:i'),
            'date' => date('Y-m-d'),
            'gold' => array(
                'sell' => 'Rp —',
                'buy' => 'Rp —'
            ),
            'silver' => array(
                'sell' => 'Rp —',
                'buy' => 'Rp —'
            )
        )
    );
}

// Support JSONP callback
if (isset($_GET['callback']) && !empty($_GET['callback'])) {
    $callback = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['callback']);
    echo $callback . '(' . json_encode($response) . ');';
} else {
    echo json_encode($response, JSON_PRETTY_PRINT);
}
?>