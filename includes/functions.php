<?php

/**
 * Initialize and get session ID
 * @return string
 * @throws Exception If session cannot be started
 */
function initializeSession() {
    try {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            
            // Configure session security
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', 1); // Si HTTPS est utilisé
            
            // Vérifier que la session a été démarrée
            if (session_status() !== PHP_SESSION_ACTIVE) {
                throw new Exception('Impossible de démarrer la séance');
            }
        }
        
        return session_id();
    } catch (Exception $e) {
        logError("Session initialization error", $e->getMessage());
        throw $e;
    }
}

/**
 * Format a number with thousand separator and decimal point
 * @param float $number
 * @return string
 */
function formatPrice($number) {
    if (!is_numeric($number)) {
        logError("Invalid number format", "Input: " . var_export($number, true));
        return '0,00';
    }
    return number_format($number, 2, ',', ' ');
}

/**
 * Get user cart count
 * @return int
 */
function getCartCount() {
    if (!isset($_SESSION['cart_count'])) {
        $_SESSION['cart_count'] = 0;
    }
    return (int)$_SESSION['cart_count'];
}

/**
 * Log an error message with context
 * @param string $message
 * @param string|null $context Additional context information
 */
function logError($message, $context = null) {
    $logMessage = "[" . date('Y-m-d H:i:s') . "] " . $message;
    if ($context) {
        $logMessage .= " - Context: " . $context;
    }
    error_log($logMessage);
}
