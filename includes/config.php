<?php
// --- Secure Session Settings ---
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

// Cookie settings
session_set_cookie_params([
    'lifetime' => 1800,     // 30 mins
    'path' => '/',
    'domain' => 'localhost', 
    'secure' => false,      // true if using HTTPS
    'httponly' => true,
    'samesite' => 'Lax',
]);

// Start session
session_start();


$rotationInterval = 900; // Always regenerate every 15 minutes

if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} 
else {
    $timeSinceLastRegen = time() - $_SESSION['last_regeneration'];
    if ($timeSinceLastRegen >= $rotationInterval) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}


