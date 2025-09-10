<?php
// API endpoint untuk data chart harga emas dan perak
// Usage: /api_chart_data.php?days=7

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once('config.php');
require_once('fungsi.php');

// Get days and period parameters
$days = isset($_GET['days']) && is_numeric($_GET['days']) ? intval($_GET['days']) : 7;
$period = isset($_GET['period']) ? $_GET['period'] : 'weekly';

// Limit days to prevent excessive data
if ($days > 365) $days = 365;
if ($days < 1) $days = 1;

// Get price data for the specified period
$price_data = db_select("SELECT * FROM `sa_gold_silver_price` 
                        WHERE `price_date` >= DATE_SUB(CURDATE(), INTERVAL ".$days." DAY) 
                        ORDER BY `price_date` ASC");

if (count($price_data) > 0) {
    $labels = [];
    $gold_sell = [];
    $gold_buy = [];
    $silver_sell = [];
    $silver_buy = [];
    
    foreach ($price_data as $row) {
        // Format date based on period
        switch ($period) {
            case 'daily':
                $labels[] = date('H:i', strtotime($row['price_date']));
                break;
            case 'weekly':
                $labels[] = date('d/m', strtotime($row['price_date']));
                break;
            case 'monthly':
                $labels[] = date('d M', strtotime($row['price_date']));
                break;
            case 'yearly':
                $labels[] = date('M Y', strtotime($row['price_date']));
                break;
            default:
                $labels[] = date('d/m', strtotime($row['price_date']));
        }
        
        // Add price data (focus on gold sell price for main chart)
        $gold_sell[] = floatval($row['gold_sell']);
        $gold_buy[] = floatval($row['gold_buy']);
        $silver_sell[] = floatval($row['silver_sell']);
        $silver_buy[] = floatval($row['silver_buy']);
    }
    
    $response = array(
        'success' => true,
        'period' => $days . ' hari',
        'data_count' => count($price_data),
        'labels' => $labels,
        'gold_sell' => $gold_sell,
        'gold_buy' => $gold_buy,
        'silver_sell' => $silver_sell,
        'silver_buy' => $silver_buy
    );
} else {
    // Generate sample data if no data exists
    $labels = [];
    $gold_sell = [];
    $gold_buy = [];
    $silver_sell = [];
    $silver_buy = [];
    
    // Generate sample data for the requested period
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('d/m', strtotime("-$i days"));
        $labels[] = $date;
        
        // Generate realistic sample prices with some variation
        $base_gold_sell = 1150000;
        $base_gold_buy = 1100000;
        $base_silver_sell = 15000;
        $base_silver_buy = 14500;
        
        // Add some random variation (±2%)
        $variation = (rand(-200, 200) / 10000); // -2% to +2%
        
        $gold_sell[] = $base_gold_sell + ($base_gold_sell * $variation);
        $gold_buy[] = $base_gold_buy + ($base_gold_buy * $variation);
        $silver_sell[] = $base_silver_sell + ($base_silver_sell * $variation);
        $silver_buy[] = $base_silver_buy + ($base_silver_buy * $variation);
    }
    
    $response = array(
        'success' => true,
        'period' => $days . ' hari',
        'data_count' => count($labels),
        'message' => 'Using sample data - no historical data available',
        'labels' => $labels,
        'gold_sell' => $gold_sell,
        'gold_buy' => $gold_buy,
        'silver_sell' => $silver_sell,
        'silver_buy' => $silver_buy
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