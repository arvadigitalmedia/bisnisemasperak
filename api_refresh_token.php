<?php
/**
 * API Endpoint for Token Refresh
 * Handles JWT token refresh using refresh tokens
 */

define('IS_IN_SCRIPT', 1);
require_once('config.php');
require_once('fungsi.php');
require_once('JWTAuth.php');
require_once('AuthMiddleware.php');

// Set JSON response header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit();
}

try {
    $jwtAuth = new JWTAuth();
    
    // Get refresh token from cookie or request body
    $refreshToken = null;
    
    if (isset($_COOKIE['refresh_token'])) {
        $refreshToken = $_COOKIE['refresh_token'];
    } else {
        // Try to get from request body
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['refresh_token'])) {
            $refreshToken = $input['refresh_token'];
        }
    }
    
    if (!$refreshToken) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Refresh token not provided'
        ]);
        exit();
    }
    
    // Verify refresh token
    $userId = $jwtAuth->verifyRefreshToken($refreshToken);
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid or expired refresh token'
        ]);
        exit();
    }
    
    // Get user data
    $datamember = db_row("SELECT * FROM `sa_member` WHERE `mem_id`=" . intval($userId));
    
    if (!$datamember) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit();
    }
    
    // Check if user is still active
    if ($datamember['mem_status'] == 0) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'User account is inactive'
        ]);
        exit();
    }
    
    // Generate new JWT token
    $payload = [
        'user_id' => $datamember['mem_id'],
        'email' => $datamember['mem_email'],
        'role' => $datamember['mem_role'],
        'status' => $datamember['mem_status']
    ];
    
    $newJwtToken = $jwtAuth->generateToken($payload);
    
    // Optionally generate new refresh token (token rotation)
    $rotateRefreshToken = isset($_GET['rotate']) && $_GET['rotate'] === 'true';
    $newRefreshToken = null;
    
    if ($rotateRefreshToken) {
        // Revoke old refresh token
        $jwtAuth->revokeRefreshToken($refreshToken);
        
        // Generate new refresh token
        $newRefreshToken = $jwtAuth->generateRefreshToken($userId);
        
        // Set new refresh token cookie
        setcookie('refresh_token', $newRefreshToken, time() + (7 * 24 * 3600), '/', '', false, true);
    }
    
    // Set new JWT token cookie
    setcookie('jwt_token', $newJwtToken, time() + 3600, '/', '', false, true);
    
    // Update last login time
    db_query("UPDATE `sa_member` SET `mem_lastlogin`='" . date('Y-m-d H:i:s') . "' WHERE `mem_id`=" . intval($userId));
    
    // Return success response
    $response = [
        'success' => true,
        'message' => 'Token refreshed successfully',
        'data' => [
            'access_token' => $newJwtToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'user' => [
                'id' => $datamember['mem_id'],
                'email' => $datamember['mem_email'],
                'name' => $datamember['mem_nama'],
                'role' => $datamember['mem_role'],
                'status' => $datamember['mem_status']
            ]
        ]
    ];
    
    if ($newRefreshToken) {
        $response['data']['refresh_token'] = $newRefreshToken;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ]);
}
?>