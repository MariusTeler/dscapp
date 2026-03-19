<?php
	require_once(__DIR__ . "/../config/config.php");
	
	// Sessions Vars
	$vSessDir = $config["sessions"]["dir"];

	session_save_path($vSessDir);
	ini_set('session.use_strict_mode', 1);
	ini_set('session.use_only_cookies', 1);
	ini_set('session.use_trans_sid', 0);
	ini_set('session.gc_probability', 1);
	ini_set("session.gc_maxlifetime", "36000");
	session_cache_limiter('nocache');
	session_set_cookie_params([
		'lifetime' => 36000,
		'path' => '/',
		'domain' => $config["domain"],
		'secure' => $config["secure"],
		'httponly' => 1,
		'samesite' => 'Strict'
	]);
	
	session_start();
	// Regenerate session ID periodically to prevent session fixation
    if (!isset($_SESSION['last_regeneration']) || 
        (time() - $_SESSION['last_regeneration']) > 1800) {
        // Regenerate session ID every 30 minutes (1800 seconds)
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }