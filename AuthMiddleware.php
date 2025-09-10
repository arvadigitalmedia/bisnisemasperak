<?php
/**
 * Authentication Middleware
 * Proteksi halaman dengan JWT Authentication
 */

class AuthMiddleware {
    private $jwtAuth;
    
    public function __construct() {
        require_once(__DIR__ . '/JWTAuth.php');
        $this->jwtAuth = new JWTAuth();
    }
    
    /**
     * Check if user is authenticated
     */
    public function requireAuth($minRole = 1) {
        $userId = is_login();
        
        if (!$userId) {
            $this->redirectToLogin();
            return false;
        }
        
        // Check role if specified
        if ($minRole > 1) {
            $user = $this->getCurrentUser($userId);
            if (!$user || $user['mem_role'] < $minRole) {
                $this->accessDenied();
                return false;
            }
        }
        
        return $userId;
    }
    
    /**
     * Check if user has specific role
     */
    public function requireRole($requiredRole) {
        $userId = is_login();
        
        if (!$userId) {
            $this->redirectToLogin();
            return false;
        }
        
        $user = $this->getCurrentUser($userId);
        if (!$user || $user['mem_role'] != $requiredRole) {
            $this->accessDenied();
            return false;
        }
        
        return $userId;
    }
    
    /**
     * Check if user is admin
     */
    public function requireAdmin() {
        return $this->requireRole(9);
    }
    
    /**
     * Check if user is staff or admin
     */
    public function requireStaff() {
        return $this->requireAuth(5);
    }
    
    /**
     * Get current user data
     */
    public function getCurrentUser($userId = null) {
        if (!$userId) {
            $userId = is_login();
        }
        
        if (!$userId) {
            return false;
        }
        
        return db_row("SELECT * FROM `sa_member` WHERE `mem_id`=" . intval($userId));
    }
    
    /**
     * Get user data from JWT token
     */
    public function getUserFromToken() {
        if (isset($_COOKIE['jwt_token'])) {
            $payload = $this->jwtAuth->verifyToken($_COOKIE['jwt_token']);
            if ($payload) {
                return $payload;
            }
        }
        
        return false;
    }
    
    /**
     * Check if user has permission for specific action
     */
    public function hasPermission($permission, $userId = null) {
        if (!$userId) {
            $userId = is_login();
        }
        
        if (!$userId) {
            return false;
        }
        
        $user = $this->getCurrentUser($userId);
        if (!$user) {
            return false;
        }
        
        // Define permissions based on role
        $permissions = [
            1 => ['view_profile', 'edit_profile', 'view_orders', 'view_commission'],
            2 => ['view_profile', 'edit_profile', 'view_orders', 'view_commission', 'view_network'],
            5 => ['view_profile', 'edit_profile', 'view_orders', 'view_commission', 'view_network', 'manage_orders', 'view_reports'],
            9 => ['*'] // Admin has all permissions
        ];
        
        $userRole = $user['mem_role'];
        
        // Admin has all permissions
        if ($userRole == 9) {
            return true;
        }
        
        // Check if user role has the required permission
        if (isset($permissions[$userRole])) {
            return in_array($permission, $permissions[$userRole]);
        }
        
        return false;
    }
    
    /**
     * Redirect to login page
     */
    private function redirectToLogin() {
        global $weburl;
        $currentUrl = $_SERVER['REQUEST_URI'];
        $redirectParam = '?redirect=' . urlencode($currentUrl);
        
        header('Location: ' . $weburl . 'login' . $redirectParam);
        exit();
    }
    
    /**
     * Show access denied page
     */
    private function accessDenied() {
        http_response_code(403);
        include(__DIR__ . '/theme/simple/dash403.php');
        exit();
    }
    
    /**
     * API Authentication for AJAX requests
     */
    public function apiAuth($minRole = 1) {
        $userId = is_login();
        
        if (!$userId) {
            $this->apiError('Unauthorized', 401);
            return false;
        }
        
        if ($minRole > 1) {
            $user = $this->getCurrentUser($userId);
            if (!$user || $user['mem_role'] < $minRole) {
                $this->apiError('Forbidden', 403);
                return false;
            }
        }
        
        return $userId;
    }
    
    /**
     * Return API error response
     */
    private function apiError($message, $code = 400) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message,
            'code' => $code
        ]);
        exit();
    }
    
    /**
     * Logout user and clear tokens
     */
    public function logout() {
        // Revoke refresh token if exists
        if (isset($_COOKIE['refresh_token'])) {
            $this->jwtAuth->revokeRefreshToken($_COOKIE['refresh_token']);
        }
        
        // Clear all authentication cookies
        setcookie('jwt_token', '', time() - 3600, '/', '', false, true);
        setcookie('refresh_token', '', time() - 3600, '/', '', false, true);
        setcookie('authentication', '', time() - 3600, '/'); // Old cookie
        
        global $weburl;
        header('Location: ' . $weburl . 'login');
        exit();
    }
    
    /**
     * Clean expired tokens (should be called periodically)
     */
    public function cleanupTokens() {
        $this->jwtAuth->cleanExpiredTokens();
    }
}

/**
 * Helper functions for easy access
 */
function requireAuth($minRole = 1) {
    $middleware = new AuthMiddleware();
    return $middleware->requireAuth($minRole);
}

function requireAdmin() {
    $middleware = new AuthMiddleware();
    return $middleware->requireAdmin();
}

function requireStaff() {
    $middleware = new AuthMiddleware();
    return $middleware->requireStaff();
}

function getCurrentUser($userId = null) {
    $middleware = new AuthMiddleware();
    return $middleware->getCurrentUser($userId);
}

function hasPermission($permission, $userId = null) {
    $middleware = new AuthMiddleware();
    return $middleware->hasPermission($permission, $userId);
}

function logoutUser() {
    $middleware = new AuthMiddleware();
    $middleware->logout();
}
?>