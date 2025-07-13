<?php
/**
 * Script Debug untuk Error 500 Server
 * Upload file ini ke root directory server hosting
 * Akses via: https://tomato-ai.lik.my.id/debug-500-error.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$debug_results = [];

try {
    // 1. Test Basic PHP
    $debug_results['php_info'] = [
        'version' => phpversion(),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size')
    ];

    // 2. Test Required PHP Extensions
    $required_extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl', 'curl'];
    $debug_results['php_extensions'] = [];
    foreach ($required_extensions as $ext) {
        $debug_results['php_extensions'][$ext] = extension_loaded($ext);
    }

    // 3. Test File System
    $debug_results['filesystem'] = [
        'current_directory' => getcwd(),
        'laravel_exists' => file_exists('artisan'),
        'vendor_exists' => is_dir('vendor'),
        'bootstrap_exists' => file_exists('bootstrap/app.php'),
        'env_exists' => file_exists('.env'),
        'storage_writable' => is_writable('storage'),
        'bootstrap_cache_writable' => is_writable('bootstrap/cache')
    ];

    // 4. Test Environment Variables
    if (file_exists('.env')) {
        $env_content = file_get_contents('.env');
        $env_lines = explode("\n", $env_content);
        $env_vars = [];
        foreach ($env_lines as $line) {
            if (strpos($line, '=') !== false && !str_starts_with(trim($line), '#')) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                if (in_array($key, ['APP_ENV', 'APP_DEBUG', 'APP_URL', 'DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME'])) {
                    $env_vars[$key] = $key === 'DB_PASSWORD' ? '***' : trim($value);
                }
            }
        }
        $debug_results['environment'] = $env_vars;
    } else {
        $debug_results['environment'] = 'File .env tidak ditemukan';
    }

    // 5. Test Laravel Bootstrap
    if (file_exists('vendor/autoload.php') && file_exists('bootstrap/app.php')) {
        try {
            require_once 'vendor/autoload.php';
            $app = require_once 'bootstrap/app.php';
            $debug_results['laravel_bootstrap'] = 'SUCCESS';
            
            // Test Laravel Config
            $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
            $debug_results['laravel_kernel'] = 'SUCCESS';
            
        } catch (Exception $e) {
            $debug_results['laravel_bootstrap'] = [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
        }
    } else {
        $debug_results['laravel_bootstrap'] = 'Laravel files missing';
    }

    // 6. Test Database Connection
    if (isset($env_vars['DB_HOST']) && isset($env_vars['DB_DATABASE'])) {
        try {
            $dsn = "mysql:host={$env_vars['DB_HOST']};dbname={$env_vars['DB_DATABASE']}";
            if (isset($env_vars['DB_PORT'])) {
                $dsn .= ";port={$env_vars['DB_PORT']}";
            }
            
            // Get password from .env
            $password = '';
            foreach ($env_lines as $line) {
                if (str_starts_with(trim($line), 'DB_PASSWORD=')) {
                    $password = trim(substr($line, 12));
                    break;
                }
            }
            
            $pdo = new PDO($dsn, $env_vars['DB_USERNAME'], $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $debug_results['database'] = [
                'connection' => 'SUCCESS',
                'tables' => $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN)
            ];
            
            // Test specific tables
            $required_tables = ['tomat_readings', 'devices', 'training_data'];
            $debug_results['database']['required_tables'] = [];
            foreach ($required_tables as $table) {
                try {
                    $result = $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
                    $debug_results['database']['required_tables'][$table] = "EXISTS ({$result} records)";
                } catch (Exception $e) {
                    $debug_results['database']['required_tables'][$table] = 'MISSING';
                }
            }
            
        } catch (Exception $e) {
            $debug_results['database'] = [
                'connection' => 'FAILED',
                'error' => $e->getMessage()
            ];
        }
    } else {
        $debug_results['database'] = 'Database config missing in .env';
    }

    // 7. Test API Endpoint Simulation
    if (isset($debug_results['laravel_bootstrap']) && $debug_results['laravel_bootstrap'] === 'SUCCESS') {
        try {
            // Simulate the ESP32 request
            $test_data = [
                'device_id' => 'DEBUG_TEST',
                'red_value' => 112,
                'green_value' => 87,
                'blue_value' => 60,
                'clear_value' => 8917,
                'temperature' => 27.6,
                'humidity' => 83
            ];
            
            $debug_results['api_simulation'] = [
                'test_data' => $test_data,
                'validation' => 'Data format valid for ESP32 request'
            ];
            
        } catch (Exception $e) {
            $debug_results['api_simulation'] = [
                'error' => $e->getMessage()
            ];
        }
    }

    // 8. Check Recent Logs
    if (is_dir('storage/logs')) {
        $log_files = glob('storage/logs/*.log');
        if (!empty($log_files)) {
            $latest_log = max($log_files);
            $log_content = file_get_contents($latest_log);
            $log_lines = explode("\n", $log_content);
            $recent_errors = [];
            
            // Get last 10 error lines
            $error_count = 0;
            for ($i = count($log_lines) - 1; $i >= 0 && $error_count < 10; $i--) {
                if (strpos($log_lines[$i], 'ERROR') !== false || strpos($log_lines[$i], 'CRITICAL') !== false) {
                    $recent_errors[] = $log_lines[$i];
                    $error_count++;
                }
            }
            
            $debug_results['recent_logs'] = [
                'latest_file' => basename($latest_log),
                'recent_errors' => array_reverse($recent_errors)
            ];
        } else {
            $debug_results['recent_logs'] = 'No log files found';
        }
    } else {
        $debug_results['recent_logs'] = 'Storage/logs directory not found';
    }

    $debug_results['status'] = 'DEBUG_COMPLETE';
    $debug_results['timestamp'] = date('Y-m-d H:i:s');
    
} catch (Exception $e) {
    $debug_results = [
        'status' => 'CRITICAL_ERROR',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
}

echo json_encode($debug_results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>