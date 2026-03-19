<?php
/**
 * Router script for PHP built-in server
 * Replicates Apache mod_rewrite behavior
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve real files (assets, favicon, etc.) directly
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Strip leading slash and set as $_GET['act']
$act = ltrim($uri, '/');
if (!empty($act) && !isset($_GET['act'])) {
    $_GET['act'] = $act;
    // Also add to $_SERVER['QUERY_STRING']
    $qs = 'act=' . urlencode($act);
    if (!empty($_SERVER['QUERY_STRING'])) {
        $qs .= '&' . $_SERVER['QUERY_STRING'];
    }
    $_SERVER['QUERY_STRING'] = $qs;
    parse_str($_SERVER['QUERY_STRING'], $_GET);
}

require __DIR__ . '/index.php';
