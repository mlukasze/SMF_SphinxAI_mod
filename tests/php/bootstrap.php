<?php

declare(strict_types=1);

/**
 * PHPUnit Bootstrap File for SMF Sphinx AI Search Plugin Tests
 */

// Define test environment
define('TESTING', true);
define('SMF_VERSION', '2.1.4');

// Set up error reporting
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', '1');

// Set timezone
date_default_timezone_set('UTC');

// Include Composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Mock SMF functions and globals that are required
if (!function_exists('loadLanguage')) {
    function loadLanguage($template, $lang = '', $fatal = true, $force_reload = false) {
        return true;
    }
}

if (!function_exists('cache_get_data')) {
    function cache_get_data($key, $ttl = null) {
        return null;
    }
}

if (!function_exists('cache_put_data')) {
    function cache_put_data($key, $value, $ttl = null) {
        return true;
    }
}

if (!function_exists('db_query')) {
    function db_query($identifier, $db_string, $db_values = array()) {
        return true;
    }
}

if (!function_exists('db_fetch_assoc')) {
    function db_fetch_assoc($request) {
        return false;
    }
}

if (!function_exists('db_free_result')) {
    function db_free_result($request) {
        return true;
    }
}

if (!function_exists('smf_db_quote')) {
    function smf_db_quote($string, $connection = null) {
        return "'" . addslashes($string) . "'";
    }
}

// Mock global variables
global $smcFunc, $modSettings, $boarddir, $sourcedir, $user_info, $context, $txt;

$smcFunc = [
    'db_query' => 'db_query',
    'db_fetch_assoc' => 'db_fetch_assoc',
    'db_free_result' => 'db_free_result',
    'db_quote' => 'smf_db_quote',
];

$modSettings = [
    'sphinx_ai_enabled' => '1',
    'sphinx_ai_model_path' => '/tmp/test_model',
    'sphinx_ai_max_results' => '50',
    'cache_enable' => '1',
    'cache_memcached' => '',
];

$boarddir = __DIR__ . '/../../';
$sourcedir = $boarddir . 'Sources';

$user_info = [
    'id' => 1,
    'username' => 'testuser',
    'name' => 'Test User',
    'email' => 'test@example.com',
    'groups' => [1],
    'is_admin' => true,
    'is_guest' => false,
];

$context = [
    'user' => $user_info,
    'session_id' => 'test_session_id',
    'session_var' => 'sesc',
];

$txt = [
    'sphinx_ai_search' => 'AI Search',
    'sphinx_ai_no_results' => 'No results found',
    'sphinx_ai_error' => 'Search error occurred',
];

// Set up test directories
$testDirs = [
    __DIR__ . '/../coverage',
    __DIR__ . '/../logs',
    __DIR__ . '/../reports',
];

foreach ($testDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Load test utilities
require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/MockFactory.php';
