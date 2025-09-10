<?php
/**
 * Simple JWT Authentication Library
 * Upgrade keamanan untuk SimpleAff Plus
 */

class JWTAuth {
    private $secret;
    private $algorithm = 'HS256';
    private $expiration = 3600; // 1 hour
    
    public function __construct($secret = null) {
        $this->secret = $secret ?: SECRET;
    }
    
    /**
     * Generate JWT Token
     */
    public function generateToken($payload) {
        $header = json_encode([
            'typ' => 'JWT',
            'alg' => $this->algorithm
        ]);
        
        $payload['iat'] = time();
        $payload['exp'] = time() + $this->expiration;
        $payload = json_encode($payload);
        
        $base64Header = $this->base64UrlEncode($header);
        $base64Payload = $this->base64UrlEncode($payload);
        
        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, $this->secret, true);
        $base64Signature = $this->base64UrlEncode($signature);
        
        return $base64Header . '.' . $base64Payload . '.' . $base64Signature;
    }
    
    /**
     * Verify JWT Token
     */
    public function verifyToken($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        list($base64Header, $base64Payload, $base64Signature) = $parts;
        
        // Verify signature
        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, $this->secret, true);
        $expectedSignature = $this->base64UrlEncode($signature);
        
        if (!hash_equals($base64Signature, $expectedSignature)) {
            return false;
        }
        
        // Decode payload
        $payload = json_decode($this->base64UrlDecode($base64Payload), true);
        
        if (!$payload) {
            return false;
        }
        
        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    }
    
    /**
     * Generate Refresh Token
     */
    public function generateRefreshToken($userId) {
        $token = bin2hex(random_bytes(32));
        $expiry = time() + (7 * 24 * 3600); // 7 days
        
        // Store in database
        global $con;
        db_query("INSERT INTO `sa_refresh_tokens` (`user_id`, `token`, `expires_at`) VALUES ('" . cek($userId) . "', '" . cek($token) . "', '" . date('Y-m-d H:i:s', $expiry) . "')");
        
        return $token;
    }
    
    /**
     * Verify Refresh Token
     */
    public function verifyRefreshToken($token) {
        $result = db_row("SELECT * FROM `sa_refresh_tokens` WHERE `token`='" . cek($token) . "' AND `expires_at` > NOW() AND `is_revoked`=0");
        
        if ($result) {
            return $result['user_id'];
        }
        
        return false;
    }
    
    /**
     * Revoke Refresh Token
     */
    public function revokeRefreshToken($token) {
        db_query("UPDATE `sa_refresh_tokens` SET `is_revoked`=1 WHERE `token`='" . cek($token) . "'");
    }
    
    /**
     * Clean expired tokens
     */
    public function cleanExpiredTokens() {
        db_query("DELETE FROM `sa_refresh_tokens` WHERE `expires_at` < NOW()");
    }
    
    /**
     * Base64 URL Encode
     */
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL Decode
     */
    private function base64UrlDecode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
    
    /**
     * Set token expiration time
     */
    public function setExpiration($seconds) {
        $this->expiration = $seconds;
    }
    
    /**
     * Get token from Authorization header
     */
    public function getTokenFromHeader() {
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    /**
     * Get token from cookie (fallback)
     */
    public function getTokenFromCookie($cookieName = 'jwt_token') {
        return isset($_COOKIE[$cookieName]) ? $_COOKIE[$cookieName] : null;
    }
}

/**
 * Create refresh tokens table if not exists
 */
function createRefreshTokensTable() {
    if (!db_var("SHOW TABLES LIKE 'sa_refresh_tokens'")) {
        db_query("CREATE TABLE `sa_refresh_tokens` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `user_id` bigint(20) NOT NULL,
            `token` varchar(255) NOT NULL,
            `expires_at` datetime NOT NULL,
            `is_revoked` tinyint(1) DEFAULT 0,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `token` (`token`),
            KEY `user_id` (`user_id`),
            KEY `expires_at` (`expires_at`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }
}

// Initialize table on first load
if (defined('IS_IN_SCRIPT')) {
    createRefreshTokensTable();
}
?>