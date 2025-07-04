<?php
/**
 * Sphinx AI Search Requirements Checker
 * 
 * Checks system requirements and plugin dependencies.
 * Provides detailed diagnostics for troubleshooting.
 * 
 * @package SphinxAISearch
 * @subpackage Utils
 */

declare(strict_types=1);

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * System requirements checker for Sphinx AI Search
 * 
 * Validates:
 * - PHP version and extensions
 * - SMF version compatibility
 * - File system permissions
 * - Python environment
 * - Directory structure
 */
class SphinxAIRequirementsChecker
{
	/**
	 * Minimum requirements
	 */
	private const REQUIREMENTS = [
		'php_version' => '7.4.0',
		'smf_version' => '2.1.0',
		'python_version' => '3.8.0',
		'required_php_extensions' => ['json', 'curl', 'mbstring'],
		'required_directories' => ['SphinxAI', 'php', 'cache'],
		'required_files' => ['SphinxAI/main.py', 'php/core/SphinxAIHookManager.php'],
	];

	/**
	 * Check all requirements
	 * 
	 * @return array Comprehensive requirements check result
	 */
	public static function checkAll(): array
	{
		$results = [
			'overall_status' => true,
			'checks' => [],
			'warnings' => [],
			'errors' => [],
			'info' => [],
		];

		// Check PHP requirements
		$php_check = self::checkPhpRequirements();
		$results['checks']['php'] = $php_check;
		if (!$php_check['status']) {
			$results['overall_status'] = false;
			$results['errors'] = array_merge($results['errors'], $php_check['messages']);
		}

		// Check SMF requirements
		$smf_check = self::checkSmfRequirements();
		$results['checks']['smf'] = $smf_check;
		if (!$smf_check['status']) {
			$results['overall_status'] = false;
			$results['errors'] = array_merge($results['errors'], $smf_check['messages']);
		}

		// Check file system
		$fs_check = self::checkFileSystem();
		$results['checks']['filesystem'] = $fs_check;
		if (!$fs_check['status']) {
			$results['overall_status'] = false;
			$results['errors'] = array_merge($results['errors'], $fs_check['messages']);
		}

		// Check Python environment (warnings only)
		$python_check = self::checkPythonEnvironment();
		$results['checks']['python'] = $python_check;
		if (!$python_check['status']) {
			$results['warnings'] = array_merge($results['warnings'], $python_check['messages']);
		}

		// Check optional features
		$optional_check = self::checkOptionalFeatures();
		$results['checks']['optional'] = $optional_check;
		$results['info'] = array_merge($results['info'], $optional_check['messages']);

		return $results;
	}

	/**
	 * Get simple requirements check for backward compatibility
	 * 
	 * @return array Array with 'status' (bool) and 'messages' (array)
	 */
	public static function checkRequirements(): array
	{
		$full_check = self::checkAll();
		
		return [
			'status' => $full_check['overall_status'],
			'messages' => array_merge($full_check['errors'], $full_check['warnings']),
		];
	}

	/**
	 * Check PHP requirements
	 * 
	 * @return array PHP requirements check result
	 */
	public static function checkPhpRequirements(): array
	{
		$status = true;
		$messages = [];

		// Check PHP version
		if (version_compare(PHP_VERSION, self::REQUIREMENTS['php_version'], '<')) {
			$status = false;
			$messages[] = sprintf(
				'PHP %s or higher is required. Current version: %s',
				self::REQUIREMENTS['php_version'],
				PHP_VERSION
			);
		}

		// Check required extensions
		foreach (self::REQUIREMENTS['required_php_extensions'] as $extension) {
			if (!extension_loaded($extension)) {
				$status = false;
				$messages[] = "Required PHP extension missing: {$extension}";
			}
		}

		// Check memory limit
		$memory_limit = ini_get('memory_limit');
		$memory_bytes = self::parseMemoryLimit($memory_limit);
		$min_memory = 128 * 1024 * 1024; // 128MB

		if ($memory_bytes > 0 && $memory_bytes < $min_memory) {
			$status = false;
			$messages[] = "Insufficient memory limit. Minimum 128MB required, current: {$memory_limit}";
		}

		// Check execution time limit
		$max_execution_time = ini_get('max_execution_time');
		if ($max_execution_time > 0 && $max_execution_time < 30) {
			$messages[] = "Low max_execution_time ({$max_execution_time}s). Consider increasing to 30s or more.";
		}

		return [
			'status' => $status,
			'messages' => $messages,
			'details' => [
				'php_version' => PHP_VERSION,
				'memory_limit' => $memory_limit,
				'max_execution_time' => $max_execution_time,
				'extensions' => array_map('extension_loaded', self::REQUIREMENTS['required_php_extensions']),
			],
		];
	}

	/**
	 * Check SMF requirements
	 * 
	 * @return array SMF requirements check result
	 */
	public static function checkSmfRequirements(): array
	{
		$status = true;
		$messages = [];

		// Check SMF version
		$smf_version = SMF_VERSION ?? '2.0.0';
		if (version_compare($smf_version, self::REQUIREMENTS['smf_version'], '<')) {
			$status = false;
			$messages[] = sprintf(
				'SMF %s or higher is required. Current version: %s',
				self::REQUIREMENTS['smf_version'],
				$smf_version
			);
		}

		// Check database connection
		global $smcFunc;
		if (empty($smcFunc) || !isset($smcFunc['db_query'])) {
			$status = false;
			$messages[] = 'SMF database connection not available';
		}

		// Check required SMF functions
		$required_functions = ['updateSettings', 'removeSettings', 'log_error'];
		foreach ($required_functions as $function) {
			if (!function_exists($function)) {
				$status = false;
				$messages[] = "Required SMF function missing: {$function}";
			}
		}

		return [
			'status' => $status,
			'messages' => $messages,
			'details' => [
				'smf_version' => $smf_version,
				'database_available' => !empty($smcFunc),
				'functions_available' => array_map('function_exists', $required_functions),
			],
		];
	}

	/**
	 * Check file system requirements
	 * 
	 * @return array File system check result
	 */
	public static function checkFileSystem(): array
	{
		$status = true;
		$messages = [];
		$plugin_dir = dirname(dirname(__DIR__));

		// Check required directories
		foreach (self::REQUIREMENTS['required_directories'] as $dir) {
			$full_path = $plugin_dir . '/' . $dir;
			if (!is_dir($full_path)) {
				$status = false;
				$messages[] = "Required directory missing: {$dir}";
			}
		}

		// Check required files
		foreach (self::REQUIREMENTS['required_files'] as $file) {
			$full_path = $plugin_dir . '/' . $file;
			if (!file_exists($full_path)) {
				$status = false;
				$messages[] = "Required file missing: {$file}";
			}
		}

		// Check write permissions
		$writable_dirs = ['cache', 'logs'];
		foreach ($writable_dirs as $dir) {
			$full_path = $plugin_dir . '/' . $dir;
			
			// Create directory if it doesn't exist
			if (!is_dir($full_path)) {
				if (!mkdir($full_path, 0755, true)) {
					$status = false;
					$messages[] = "Cannot create directory: {$dir}";
					continue;
				}
			}

			// Check if writable
			if (!is_writable($full_path)) {
				$status = false;
				$messages[] = "Directory is not writable: {$dir}";
			}
		}

		return [
			'status' => $status,
			'messages' => $messages,
			'details' => [
				'plugin_directory' => $plugin_dir,
				'directories_exist' => array_map(function($dir) use ($plugin_dir) {
					return is_dir($plugin_dir . '/' . $dir);
				}, self::REQUIREMENTS['required_directories']),
				'files_exist' => array_map(function($file) use ($plugin_dir) {
					return file_exists($plugin_dir . '/' . $file);
				}, self::REQUIREMENTS['required_files']),
			],
		];
	}

	/**
	 * Check Python environment
	 * 
	 * @return array Python environment check result
	 */
	public static function checkPythonEnvironment(): array
	{
		$status = true;
		$messages = [];
		$details = [];

		// Check if Python is available
		$python_commands = ['python3', 'python'];
		$python_found = false;
		$python_version = null;

		foreach ($python_commands as $cmd) {
			$output = [];
			$return_var = 0;
			exec("{$cmd} --version 2>&1", $output, $return_var);
			
			if ($return_var === 0 && !empty($output)) {
				$python_found = true;
				$version_line = implode(' ', $output);
				if (preg_match('/Python\s+(\d+\.\d+\.\d+)/', $version_line, $matches)) {
					$python_version = $matches[1];
					$details['python_command'] = $cmd;
					$details['python_version'] = $python_version;
					break;
				}
			}
		}

		if (!$python_found) {
			$status = false;
			$messages[] = 'Python not found in system PATH. Please install Python 3.8 or higher.';
		} elseif ($python_version && version_compare($python_version, self::REQUIREMENTS['python_version'], '<')) {
			$status = false;
			$messages[] = sprintf(
				'Python %s or higher is required. Current version: %s',
				self::REQUIREMENTS['python_version'],
				$python_version
			);
		}

		// Check Python packages (if Python is available)
		if ($python_found && isset($details['python_command'])) {
			$required_packages = ['torch', 'transformers', 'numpy', 'requests'];
			$package_status = [];

			foreach ($required_packages as $package) {
				$output = [];
				$return_var = 0;
				exec("{$details['python_command']} -c \"import {$package}; print('OK')\" 2>/dev/null", $output, $return_var);
				
				$package_available = ($return_var === 0 && in_array('OK', $output));
				$package_status[$package] = $package_available;

				if (!$package_available) {
					$messages[] = "Python package not available: {$package}. Install with: pip install {$package}";
				}
			}

			$details['packages'] = $package_status;
		}

		return [
			'status' => $status,
			'messages' => $messages,
			'details' => $details,
		];
	}

	/**
	 * Check optional features
	 * 
	 * @return array Optional features check result
	 */
	public static function checkOptionalFeatures(): array
	{
		$messages = [];
		$details = [];

		// Check for GPU support
		if (extension_loaded('cuda') || function_exists('nvidia_ml_py')) {
			$messages[] = 'GPU acceleration may be available for faster AI processing';
			$details['gpu_support'] = true;
		} else {
			$messages[] = 'GPU acceleration not detected. AI processing will use CPU (slower)';
			$details['gpu_support'] = false;
		}

		// Check for advanced PHP features
		if (function_exists('opcache_get_status')) {
			$opcache_status = opcache_get_status();
			if ($opcache_status && $opcache_status['opcache_enabled']) {
				$messages[] = 'OPcache is enabled for better PHP performance';
				$details['opcache'] = true;
			} else {
				$messages[] = 'OPcache is available but not enabled. Consider enabling for better performance';
				$details['opcache'] = false;
			}
		}

		// Check for APCu cache
		if (extension_loaded('apcu') && function_exists('apcu_enabled') && apcu_enabled()) {
			$messages[] = 'APCu cache is available for improved caching performance';
			$details['apcu'] = true;
		} else {
			$messages[] = 'APCu cache not available. Consider installing for better caching performance';
			$details['apcu'] = false;
		}

		return [
			'status' => true, // Optional features don't affect overall status
			'messages' => $messages,
			'details' => $details,
		];
	}

	/**
	 * Generate a detailed requirements report
	 * 
	 * @return string HTML formatted requirements report
	 */
	public static function generateReport(): string
	{
		$check_results = self::checkAll();
		
		$html = '<div class="sphinx-ai-requirements-report">';
		$html .= '<h3>Sphinx AI Search - System Requirements Report</h3>';

		// Overall status
		$status_class = $check_results['overall_status'] ? 'success' : 'error';
		$status_text = $check_results['overall_status'] ? 'PASSED' : 'FAILED';
		$html .= "<div class=\"status-{$status_class}\">Overall Status: {$status_text}</div>";

		// Errors
		if (!empty($check_results['errors'])) {
			$html .= '<div class="errors"><h4>Errors (Must Fix):</h4><ul>';
			foreach ($check_results['errors'] as $error) {
				$html .= '<li class="error">' . htmlspecialchars($error) . '</li>';
			}
			$html .= '</ul></div>';
		}

		// Warnings
		if (!empty($check_results['warnings'])) {
			$html .= '<div class="warnings"><h4>Warnings (Recommended):</h4><ul>';
			foreach ($check_results['warnings'] as $warning) {
				$html .= '<li class="warning">' . htmlspecialchars($warning) . '</li>';
			}
			$html .= '</ul></div>';
		}

		// Info
		if (!empty($check_results['info'])) {
			$html .= '<div class="info"><h4>Information:</h4><ul>';
			foreach ($check_results['info'] as $info) {
				$html .= '<li class="info">' . htmlspecialchars($info) . '</li>';
			}
			$html .= '</ul></div>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Parse memory limit string to bytes
	 * 
	 * @param string $memory_limit Memory limit string (e.g., "128M", "1G")
	 * @return int Memory limit in bytes
	 */
	private static function parseMemoryLimit(string $memory_limit): int
	{
		$memory_limit = trim($memory_limit);
		
		if ($memory_limit === '-1') {
			return -1; // Unlimited
		}

		$unit = strtoupper(substr($memory_limit, -1));
		$value = (int) substr($memory_limit, 0, -1);

		switch ($unit) {
			case 'G':
				return $value * 1024 * 1024 * 1024;
			case 'M':
				return $value * 1024 * 1024;
			case 'K':
				return $value * 1024;
			default:
				return (int) $memory_limit; // Assume bytes
		}
	}
}
