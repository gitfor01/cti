<?php
/**
 * PCF Configuration Update Script
 * 
 * This script allows you to configure PCF database connections for different types
 */

$configFile = __DIR__ . '/config/pcf_remote_config.php';
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $connectionType = $_POST['connection_type'] ?? 'local_sqlite';
    $isTestMode = isset($_POST['test_connection']);
    
    // Collect form data based on connection type
    $configData = [];
    $isValid = true;
    $errorMsg = '';
    
    switch ($connectionType) {
        case 'local_sqlite':
            $configData['path'] = $_POST['local_path'] ?? '';
            if (empty($configData['path'])) {
                $isValid = false;
                $errorMsg = 'Please provide a local SQLite database path.';
            }
            break;
            
        case 'remote_sqlite':
            $configData['host'] = $_POST['remote_host'] ?? '';
            $configData['username'] = $_POST['remote_username'] ?? '';
            $configData['password'] = $_POST['remote_password'] ?? '';
            $configData['path'] = $_POST['remote_path'] ?? '';
            
            if (empty($configData['host']) || empty($configData['path'])) {
                $isValid = false;
                $errorMsg = 'Please provide host and remote path for remote SQLite connection.';
            }
            break;
            
        case 'remote_mysql':
            $configData['host'] = $_POST['mysql_host'] ?? '';
            $configData['database'] = $_POST['mysql_database'] ?? '';
            $configData['username'] = $_POST['mysql_username'] ?? '';
            $configData['password'] = $_POST['mysql_password'] ?? '';
            $configData['port'] = $_POST['mysql_port'] ?? '3306';
            
            if (empty($configData['host']) || empty($configData['database'])) {
                $isValid = false;
                $errorMsg = 'Please provide host and database name for MySQL connection.';
            }
            break;
            
        case 'remote_postgresql':
            $configData['host'] = $_POST['pgsql_host'] ?? '';
            $configData['database'] = $_POST['pgsql_database'] ?? '';
            $configData['username'] = $_POST['pgsql_username'] ?? '';
            $configData['password'] = $_POST['pgsql_password'] ?? '';
            $configData['port'] = $_POST['pgsql_port'] ?? '5432';
            
            if (empty($configData['host']) || empty($configData['database'])) {
                $isValid = false;
                $errorMsg = 'Please provide host and database name for PostgreSQL connection.';
            }
            break;
    }
    
    if ($isValid) {
        // Test connection if requested
        if ($isTestMode) {
            $testResult = testPcfConnection($connectionType, $configData);
            if ($testResult['success']) {
                $message = "✓ Connection test successful! " . $testResult['message'];
                $messageType = 'success';
            } else {
                $message = "✗ Connection test failed: " . $testResult['message'];
                $messageType = 'danger';
            }
        } else {
            try {
                // Generate new configuration content
                $newConfigContent = "<?php\n";
                $newConfigContent .= "/**\n";
                $newConfigContent .= " * PCF Remote Configuration\n";
                $newConfigContent .= " * Generated on: " . date('Y-m-d H:i:s') . "\n";
                $newConfigContent .= " */\n\n";
            
            // Connection type
            $newConfigContent .= "define('PCF_CONNECTION_TYPE', '" . addslashes($connectionType) . "');\n\n";
            
            // Common timeout settings
            $newConfigContent .= "// Timeout settings\n";
            $newConfigContent .= "define('PCF_CONNECTION_TIMEOUT', 10);\n";
            $newConfigContent .= "define('PCF_QUERY_TIMEOUT', 30);\n\n";
            
            // Connection-specific configuration
            switch ($connectionType) {
                case 'local_sqlite':
                    $newConfigContent .= "// Local SQLite configuration\n";
                    $newConfigContent .= "define('PCF_SQLITE_PATH', '" . addslashes($configData['path']) . "');\n";
                    $newConfigContent .= "define('PCF_DATABASE_PATH', '" . addslashes($configData['path']) . "');\n";
                    break;
                    
                case 'remote_sqlite':
                    $newConfigContent .= "// Remote SQLite configuration\n";
                    $newConfigContent .= "define('PCF_REMOTE_SQLITE_HOST', '" . addslashes($configData['host']) . "');\n";
                    $newConfigContent .= "define('PCF_REMOTE_SQLITE_USERNAME', '" . addslashes($configData['username']) . "');\n";
                    $newConfigContent .= "define('PCF_REMOTE_SQLITE_PASSWORD', '" . addslashes($configData['password']) . "');\n";
                    $newConfigContent .= "define('PCF_REMOTE_SQLITE_PATH', '" . addslashes($configData['path']) . "');\n";
                    $newConfigContent .= "define('PCF_REMOTE_SQLITE_PORT', 22);\n";
                    $newConfigContent .= "define('PCF_REMOTE_SQLITE_METHOD', 'ssh');\n";
                    $newConfigContent .= "define('PCF_REMOTE_SQLITE_LOCAL_CACHE', '" . sys_get_temp_dir() . "/pcf_remote_cache.sqlite3');\n";
                    $newConfigContent .= "define('PCF_REMOTE_SQLITE_CACHE_DURATION', 3600);\n";
                    $newConfigContent .= "define('PCF_DATABASE_PATH', '" . addslashes($configData['path']) . "');\n";
                    $newConfigContent .= "define('PCF_SQLITE_PATH', '" . addslashes($configData['path']) . "');\n";
                    break;
                    
                case 'remote_mysql':
                    $newConfigContent .= "// MySQL configuration\n";
                    $newConfigContent .= "define('PCF_MYSQL_HOST', '" . addslashes($configData['host']) . "');\n";
                    $newConfigContent .= "define('PCF_MYSQL_DATABASE', '" . addslashes($configData['database']) . "');\n";
                    $newConfigContent .= "define('PCF_MYSQL_USERNAME', '" . addslashes($configData['username']) . "');\n";
                    $newConfigContent .= "define('PCF_MYSQL_PASSWORD', '" . addslashes($configData['password']) . "');\n";
                    $newConfigContent .= "define('PCF_MYSQL_PORT', '" . addslashes($configData['port']) . "');\n";
                    $newConfigContent .= "define('PCF_DATABASE_PATH', 'mysql://" . addslashes($configData['host']) . ":" . addslashes($configData['port']) . "/" . addslashes($configData['database']) . "');\n";
                    break;
                    
                case 'remote_postgresql':
                    $newConfigContent .= "// PostgreSQL configuration\n";
                    $newConfigContent .= "define('PCF_POSTGRESQL_HOST', '" . addslashes($configData['host']) . "');\n";
                    $newConfigContent .= "define('PCF_POSTGRESQL_DATABASE', '" . addslashes($configData['database']) . "');\n";
                    $newConfigContent .= "define('PCF_POSTGRESQL_USERNAME', '" . addslashes($configData['username']) . "');\n";
                    $newConfigContent .= "define('PCF_POSTGRESQL_PASSWORD', '" . addslashes($configData['password']) . "');\n";
                    $newConfigContent .= "define('PCF_POSTGRESQL_PORT', '" . addslashes($configData['port']) . "');\n";
                    $newConfigContent .= "define('PCF_DATABASE_PATH', 'postgresql://" . addslashes($configData['host']) . ":" . addslashes($configData['port']) . "/" . addslashes($configData['database']) . "');\n";
                    break;
            }
            
            // SSL and security settings
            $newConfigContent .= "\n// SSL and security settings\n";
            $newConfigContent .= "define('PCF_USE_SSL', false);\n";
            $newConfigContent .= "define('PCF_VERIFY_SSL', true);\n";
            $newConfigContent .= "define('PCF_SSL_CERT_PATH', '');\n";
            
            $newConfigContent .= "\n?>";
            
            // Write configuration file
            if (file_put_contents($configFile, $newConfigContent)) {
                $message = "✓ Configuration updated successfully! Connection type: " . ucfirst(str_replace('_', ' ', $connectionType));
                $messageType = 'success';
            } else {
                $message = "✗ Failed to write configuration file. Check file permissions.";
                $messageType = 'danger';
            }
            } catch (Exception $e) {
                $message = "✗ Error updating configuration: " . htmlspecialchars($e->getMessage());
                $messageType = 'danger';
            }
        }
    } else {
        $message = "✗ " . $errorMsg;
        $messageType = 'warning';
    }
}

// Read current configuration
$currentType = 'local_sqlite';
$currentConfig = [];

if (file_exists($configFile)) {
    require_once $configFile;
    if (defined('PCF_CONNECTION_TYPE')) {
        $currentType = PCF_CONNECTION_TYPE;
        
        // Load current configuration based on type
        switch ($currentType) {
            case 'local_sqlite':
                $currentConfig['path'] = defined('PCF_SQLITE_PATH') ? PCF_SQLITE_PATH : '';
                break;
            case 'remote_sqlite':
                $currentConfig['host'] = defined('PCF_REMOTE_SQLITE_HOST') ? PCF_REMOTE_SQLITE_HOST : (defined('PCF_REMOTE_HOST') ? PCF_REMOTE_HOST : '');
                $currentConfig['username'] = defined('PCF_REMOTE_SQLITE_USERNAME') ? PCF_REMOTE_SQLITE_USERNAME : (defined('PCF_REMOTE_USERNAME') ? PCF_REMOTE_USERNAME : '');
                $currentConfig['password'] = defined('PCF_REMOTE_SQLITE_PASSWORD') ? PCF_REMOTE_SQLITE_PASSWORD : (defined('PCF_REMOTE_PASSWORD') ? PCF_REMOTE_PASSWORD : '');
                $currentConfig['path'] = defined('PCF_REMOTE_SQLITE_PATH') ? PCF_REMOTE_SQLITE_PATH : (defined('PCF_REMOTE_PATH') ? PCF_REMOTE_PATH : '');
                break;
            case 'remote_mysql':
                $currentConfig['host'] = defined('PCF_MYSQL_HOST') ? PCF_MYSQL_HOST : '';
                $currentConfig['database'] = defined('PCF_MYSQL_DATABASE') ? PCF_MYSQL_DATABASE : '';
                $currentConfig['username'] = defined('PCF_MYSQL_USERNAME') ? PCF_MYSQL_USERNAME : '';
                $currentConfig['password'] = defined('PCF_MYSQL_PASSWORD') ? PCF_MYSQL_PASSWORD : '';
                $currentConfig['port'] = defined('PCF_MYSQL_PORT') ? PCF_MYSQL_PORT : '3306';
                break;
            case 'remote_postgresql':
                $currentConfig['host'] = defined('PCF_POSTGRESQL_HOST') ? PCF_POSTGRESQL_HOST : (defined('PCF_PGSQL_HOST') ? PCF_PGSQL_HOST : '');
                $currentConfig['database'] = defined('PCF_POSTGRESQL_DATABASE') ? PCF_POSTGRESQL_DATABASE : (defined('PCF_PGSQL_DATABASE') ? PCF_PGSQL_DATABASE : '');
                $currentConfig['username'] = defined('PCF_POSTGRESQL_USERNAME') ? PCF_POSTGRESQL_USERNAME : (defined('PCF_PGSQL_USERNAME') ? PCF_PGSQL_USERNAME : '');
                $currentConfig['password'] = defined('PCF_POSTGRESQL_PASSWORD') ? PCF_POSTGRESQL_PASSWORD : (defined('PCF_PGSQL_PASSWORD') ? PCF_PGSQL_PASSWORD : '');
                $currentConfig['port'] = defined('PCF_POSTGRESQL_PORT') ? PCF_POSTGRESQL_PORT : (defined('PCF_PGSQL_PORT') ? PCF_PGSQL_PORT : '5432');
                break;
        }
    }
}

/**
 * Test PCF connection with provided credentials
 * @param string $connectionType The type of connection to test
 * @param array $configData The configuration data to test
 * @return array Result array with 'success' boolean and 'message' string
 */
function testPcfConnection($connectionType, $configData) {
    try {
        switch ($connectionType) {
            case 'local_sqlite':
                return testLocalSqliteConnection($configData);
            case 'remote_sqlite':
                return testRemoteSqliteConnection($configData);
            case 'remote_mysql':
                return testMysqlConnection($configData);
            case 'remote_postgresql':
                return testPostgresqlConnection($configData);
            default:
                return ['success' => false, 'message' => 'Unknown connection type'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Test failed: ' . $e->getMessage()];
    }
}

function testLocalSqliteConnection($configData) {
    $path = $configData['path'];
    
    // Check if path is provided
    if (empty($path)) {
        return ['success' => false, 'message' => 'ERROR: Database path is required'];
    }
    
    // Check if file exists
    if (!file_exists($path)) {
        return ['success' => false, 'message' => 'FILE NOT FOUND: Database file does not exist at: ' . $path];
    }
    
    // Check if file is readable
    if (!is_readable($path)) {
        return ['success' => false, 'message' => 'PERMISSION DENIED: Database file is not readable (check file permissions): ' . $path];
    }
    
    // Check if file is writable (for database operations)
    if (!is_writable($path)) {
        return ['success' => false, 'message' => 'PERMISSION DENIED: Database file is not writable (check file permissions): ' . $path];
    }
    
    // Check if directory is writable (for SQLite journal files)
    $dir = dirname($path);
    if (!is_writable($dir)) {
        return ['success' => false, 'message' => 'PERMISSION DENIED: Database directory is not writable (SQLite needs write access to directory): ' . $dir];
    }
    
    try {
        $pdo = new PDO("sqlite:" . $path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Test a simple query to verify database structure
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' LIMIT 1");
        $result = $stmt->fetch();
        
        // Test write access with a simple operation
        $pdo->exec("PRAGMA journal_mode");
        
        return ['success' => true, 'message' => 'SUCCESS: Local SQLite database is accessible, readable, and writable'];
    } catch (PDOException $e) {
        $errorMsg = $e->getMessage();
        if (strpos($errorMsg, 'database is locked') !== false) {
            return ['success' => false, 'message' => 'DATABASE LOCKED: SQLite database is currently locked by another process'];
        } elseif (strpos($errorMsg, 'not a database') !== false) {
            return ['success' => false, 'message' => 'INVALID DATABASE: File exists but is not a valid SQLite database'];
        } elseif (strpos($errorMsg, 'disk I/O error') !== false) {
            return ['success' => false, 'message' => 'DISK ERROR: Disk I/O error accessing database file'];
        } else {
            return ['success' => false, 'message' => 'DATABASE ERROR: SQLite connection failed - ' . $errorMsg];
        }
    }
}

function testRemoteSqliteConnection($configData) {
    $host = $configData['host'];
    $username = $configData['username'];
    $password = $configData['password'];
    $remotePath = $configData['path'];
    
    // Validate required fields
    if (empty($host)) {
        return ['success' => false, 'message' => 'ERROR: Remote host is required'];
    }
    
    if (empty($remotePath)) {
        return ['success' => false, 'message' => 'ERROR: Remote database path is required'];
    }
    
    // Test SSH connection if credentials provided
    if (!empty($username)) {
        // Check if SSH2 extension is available
        if (!function_exists('ssh2_connect')) {
            return ['success' => false, 'message' => 'SSH2 EXTENSION MISSING: PHP SSH2 extension is not installed. Cannot test remote SSH connection.'];
        }
        
        try {
            // Test SSH connection
            $connection = ssh2_connect($host, 22);
            if (!$connection) {
                return ['success' => false, 'message' => 'CONNECTION FAILED: Cannot establish SSH connection to host: ' . $host . ':22'];
            }
            
            // Test authentication
            if (!empty($password)) {
                if (!ssh2_auth_password($connection, $username, $password)) {
                    return ['success' => false, 'message' => 'AUTHENTICATION FAILED: SSH password authentication failed for user: ' . $username];
                }
            } else {
                // Try public key authentication
                $homeDir = getenv('HOME') ?: '/home/' . get_current_user();
                $pubKey = $homeDir . '/.ssh/id_rsa.pub';
                $privKey = $homeDir . '/.ssh/id_rsa';
                
                if (!file_exists($pubKey) || !file_exists($privKey)) {
                    return ['success' => false, 'message' => 'SSH KEYS NOT FOUND: Public/private key files not found in ' . $homeDir . '/.ssh/'];
                }
                
                if (!ssh2_auth_pubkey_file($connection, $username, $pubKey, $privKey)) {
                    return ['success' => false, 'message' => 'AUTHENTICATION FAILED: SSH key authentication failed for user: ' . $username];
                }
            }
            
            // Test if remote file exists and is accessible
            $stream = ssh2_exec($connection, "test -f '$remotePath' && echo 'EXISTS' || echo 'NOT_EXISTS'");
            stream_set_blocking($stream, true);
            $result = trim(stream_get_contents($stream));
            fclose($stream);
            
            if ($result !== 'EXISTS') {
                return ['success' => false, 'message' => 'FILE NOT FOUND: Remote database file does not exist at: ' . $remotePath];
            }
            
            // Test file permissions
            $stream = ssh2_exec($connection, "test -r '$remotePath' && test -w '$remotePath' && echo 'ACCESSIBLE' || echo 'NO_ACCESS'");
            stream_set_blocking($stream, true);
            $permResult = trim(stream_get_contents($stream));
            fclose($stream);
            
            if ($permResult !== 'ACCESSIBLE') {
                return ['success' => false, 'message' => 'PERMISSION DENIED: Remote database file exists but is not readable/writable: ' . $remotePath];
            }
            
            // Test if it's a valid SQLite file
            $stream = ssh2_exec($connection, "file '$remotePath' | grep -i sqlite");
            stream_set_blocking($stream, true);
            $fileResult = trim(stream_get_contents($stream));
            fclose($stream);
            
            if (empty($fileResult)) {
                return ['success' => false, 'message' => 'INVALID DATABASE: Remote file exists but may not be a valid SQLite database: ' . $remotePath];
            }
            
            return ['success' => true, 'message' => 'SUCCESS: SSH connection established, remote database file exists and is accessible'];
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            if (strpos($errorMsg, 'Connection refused') !== false) {
                return ['success' => false, 'message' => 'CONNECTION REFUSED: SSH connection refused by host: ' . $host];
            } elseif (strpos($errorMsg, 'Network is unreachable') !== false) {
                return ['success' => false, 'message' => 'NETWORK ERROR: Host is unreachable: ' . $host];
            } elseif (strpos($errorMsg, 'timeout') !== false) {
                return ['success' => false, 'message' => 'CONNECTION TIMEOUT: SSH connection timed out to host: ' . $host];
            } else {
                return ['success' => false, 'message' => 'SSH ERROR: SSH connection test failed - ' . $errorMsg];
            }
        }
    } else {
        // Basic connectivity test without SSH credentials
        $fp = @fsockopen($host, 22, $errno, $errstr, 10);
        if (!$fp) {
            if ($errno == 110) {
                return ['success' => false, 'message' => 'CONNECTION TIMEOUT: Cannot reach host ' . $host . ':22 (connection timed out)'];
            } elseif ($errno == 111) {
                return ['success' => false, 'message' => 'CONNECTION REFUSED: Host ' . $host . ':22 refused connection'];
            } else {
                return ['success' => false, 'message' => 'CONNECTION FAILED: Cannot reach host ' . $host . ':22 - ' . $errstr . ' (Error: ' . $errno . ')'];
            }
        }
        fclose($fp);
        return ['success' => true, 'message' => 'PARTIAL SUCCESS: Host is reachable on port 22, but SSH credentials not provided for full test'];
    }
}

function testMysqlConnection($configData) {
    $host = $configData['host'];
    $database = $configData['database'];
    $username = $configData['username'];
    $password = $configData['password'];
    $port = $configData['port'] ?: 3306;
    
    // Validate required fields
    if (empty($host)) {
        return ['success' => false, 'message' => 'ERROR: MySQL host is required'];
    }
    
    if (empty($database)) {
        return ['success' => false, 'message' => 'ERROR: MySQL database name is required'];
    }
    
    // Test basic connectivity first
    $fp = @fsockopen($host, $port, $errno, $errstr, 10);
    if (!$fp) {
        if ($errno == 110) {
            return ['success' => false, 'message' => 'CONNECTION TIMEOUT: Cannot reach MySQL server at ' . $host . ':' . $port . ' (connection timed out)'];
        } elseif ($errno == 111) {
            return ['success' => false, 'message' => 'CONNECTION REFUSED: MySQL server at ' . $host . ':' . $port . ' refused connection'];
        } else {
            return ['success' => false, 'message' => 'CONNECTION FAILED: Cannot reach MySQL server at ' . $host . ':' . $port . ' - ' . $errstr . ' (Error: ' . $errno . ')'];
        }
    }
    fclose($fp);
    
    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10
        ]);
        
        // Test a simple query to verify database access
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        
        // Test database permissions by checking if we can show tables
        $stmt = $pdo->query("SHOW TABLES LIMIT 1");
        
        return ['success' => true, 'message' => 'SUCCESS: MySQL connection established and database is accessible'];
    } catch (PDOException $e) {
        $errorMsg = $e->getMessage();
        $errorCode = $e->getCode();
        
        // Categorize common MySQL errors
        if ($errorCode == 1045 || strpos($errorMsg, 'Access denied') !== false) {
            return ['success' => false, 'message' => 'AUTHENTICATION FAILED: Invalid MySQL credentials for user: ' . $username];
        } elseif ($errorCode == 1049 || strpos($errorMsg, 'Unknown database') !== false) {
            return ['success' => false, 'message' => 'DATABASE NOT FOUND: MySQL database "' . $database . '" does not exist'];
        } elseif ($errorCode == 2002 || strpos($errorMsg, 'Connection refused') !== false) {
            return ['success' => false, 'message' => 'CONNECTION REFUSED: MySQL server is not running or refusing connections'];
        } elseif ($errorCode == 2003 || strpos($errorMsg, "Can't connect") !== false) {
            return ['success' => false, 'message' => 'CONNECTION FAILED: Cannot connect to MySQL server at ' . $host . ':' . $port];
        } elseif (strpos($errorMsg, 'timeout') !== false) {
            return ['success' => false, 'message' => 'CONNECTION TIMEOUT: MySQL connection timed out'];
        } elseif (strpos($errorMsg, 'Too many connections') !== false) {
            return ['success' => false, 'message' => 'SERVER OVERLOADED: MySQL server has too many connections'];
        } else {
            return ['success' => false, 'message' => 'DATABASE ERROR: MySQL connection failed - ' . $errorMsg . ' (Code: ' . $errorCode . ')'];
        }
    }
}

function testPostgresqlConnection($configData) {
    $host = $configData['host'];
    $database = $configData['database'];
    $username = $configData['username'];
    $password = $configData['password'];
    $port = $configData['port'] ?: 5432;
    
    // Validate required fields
    if (empty($host)) {
        return ['success' => false, 'message' => 'ERROR: PostgreSQL host is required'];
    }
    
    if (empty($database)) {
        return ['success' => false, 'message' => 'ERROR: PostgreSQL database name is required'];
    }
    
    // Test basic connectivity first
    $fp = @fsockopen($host, $port, $errno, $errstr, 10);
    if (!$fp) {
        if ($errno == 110) {
            return ['success' => false, 'message' => 'CONNECTION TIMEOUT: Cannot reach PostgreSQL server at ' . $host . ':' . $port . ' (connection timed out)'];
        } elseif ($errno == 111) {
            return ['success' => false, 'message' => 'CONNECTION REFUSED: PostgreSQL server at ' . $host . ':' . $port . ' refused connection'];
        } else {
            return ['success' => false, 'message' => 'CONNECTION FAILED: Cannot reach PostgreSQL server at ' . $host . ':' . $port . ' - ' . $errstr . ' (Error: ' . $errno . ')'];
        }
    }
    fclose($fp);
    
    try {
        $dsn = "pgsql:host=$host;port=$port;dbname=$database";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10
        ]);
        
        // Test a simple query to verify database access
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        
        // Test database permissions by checking if we can access system tables
        $stmt = $pdo->query("SELECT tablename FROM pg_tables LIMIT 1");
        
        return ['success' => true, 'message' => 'SUCCESS: PostgreSQL connection established and database is accessible'];
    } catch (PDOException $e) {
        $errorMsg = $e->getMessage();
        $errorCode = $e->getCode();
        
        // Categorize common PostgreSQL errors
        if (strpos($errorMsg, 'authentication failed') !== false || strpos($errorMsg, 'password authentication failed') !== false) {
            return ['success' => false, 'message' => 'AUTHENTICATION FAILED: Invalid PostgreSQL credentials for user: ' . $username];
        } elseif (strpos($errorMsg, 'database') !== false && strpos($errorMsg, 'does not exist') !== false) {
            return ['success' => false, 'message' => 'DATABASE NOT FOUND: PostgreSQL database "' . $database . '" does not exist'];
        } elseif (strpos($errorMsg, 'role') !== false && strpos($errorMsg, 'does not exist') !== false) {
            return ['success' => false, 'message' => 'USER NOT FOUND: PostgreSQL user "' . $username . '" does not exist'];
        } elseif (strpos($errorMsg, 'Connection refused') !== false) {
            return ['success' => false, 'message' => 'CONNECTION REFUSED: PostgreSQL server is not running or refusing connections'];
        } elseif (strpos($errorMsg, 'could not connect to server') !== false) {
            return ['success' => false, 'message' => 'CONNECTION FAILED: Cannot connect to PostgreSQL server at ' . $host . ':' . $port];
        } elseif (strpos($errorMsg, 'timeout') !== false) {
            return ['success' => false, 'message' => 'CONNECTION TIMEOUT: PostgreSQL connection timed out'];
        } elseif (strpos($errorMsg, 'too many clients') !== false) {
            return ['success' => false, 'message' => 'SERVER OVERLOADED: PostgreSQL server has too many client connections'];
        } elseif (strpos($errorMsg, 'permission denied') !== false) {
            return ['success' => false, 'message' => 'PERMISSION DENIED: User does not have permission to access database "' . $database . '"'];
        } else {
            return ['success' => false, 'message' => 'DATABASE ERROR: PostgreSQL connection failed - ' . $errorMsg . ' (Code: ' . $errorCode . ')'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update PCF Configuration - AMT</title>
    <link href="scripts/bootstrap.min.css" rel="stylesheet">
    <link href="scripts/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1><i class="fas fa-cog"></i> Update PCF Configuration</h1>
        <p class="text-muted">Update the PCF database connection settings</p>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-database"></i> PCF Database Configuration</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="configForm">
                    <div class="mb-3">
                        <label for="connection_type" class="form-label">Connection Type</label>
                        <select class="form-control" id="connection_type" name="connection_type" onchange="toggleConnectionFields()">
                            <option value="local_sqlite" <?php echo $currentType === 'local_sqlite' ? 'selected' : ''; ?>>Local SQLite</option>
                            <option value="remote_sqlite" <?php echo $currentType === 'remote_sqlite' ? 'selected' : ''; ?>>Remote SQLite</option>
                            <option value="remote_mysql" <?php echo $currentType === 'remote_mysql' ? 'selected' : ''; ?>>Remote MySQL</option>
                            <option value="remote_postgresql" <?php echo $currentType === 'remote_postgresql' ? 'selected' : ''; ?>>Remote PostgreSQL</option>
                        </select>
                    </div>

                    <!-- Local SQLite Fields -->
                    <div id="local_sqlite_fields" class="connection-fields">
                        <div class="mb-3">
                            <label for="local_path" class="form-label">Local SQLite Database Path</label>
                            <input type="text" class="form-control" id="local_path" name="local_path" 
                                   value="<?php echo htmlspecialchars($currentConfig['path'] ?? ''); ?>" 
                                   placeholder="/path/to/your/pcf/database.sqlite3">
                            <div class="form-text">
                                <strong>Examples:</strong><br>
                                <code>/Users/[username]/Downloads/Others/CTI Proj/pcf/configuration/database.sqlite3</code><br>
                                <code>/opt/pcf/configuration/database.sqlite3</code><br>
                                <code>./pcf/configuration/database.sqlite3</code>
                            </div>
                        </div>
                    </div>

                    <!-- Remote SQLite Fields -->
                    <div id="remote_sqlite_fields" class="connection-fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="remote_host" class="form-label">Remote Host/IP</label>
                                <input type="text" class="form-control" id="remote_host" name="remote_host" 
                                       value="<?php echo htmlspecialchars($currentConfig['host'] ?? ''); ?>" 
                                       placeholder="192.168.1.100 or server.example.com">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="remote_path" class="form-label">Remote Database Path</label>
                                <input type="text" class="form-control" id="remote_path" name="remote_path" 
                                       value="<?php echo htmlspecialchars($currentConfig['path'] ?? ''); ?>" 
                                       placeholder="/path/to/pcf/database.sqlite3">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="remote_username" class="form-label">Username (Optional)</label>
                                <input type="text" class="form-control" id="remote_username" name="remote_username" 
                                       value="<?php echo htmlspecialchars($currentConfig['username'] ?? ''); ?>" 
                                       placeholder="SSH username">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="remote_password" class="form-label">Password (Optional)</label>
                                <input type="password" class="form-control" id="remote_password" name="remote_password" 
                                       value="<?php echo htmlspecialchars($currentConfig['password'] ?? ''); ?>" 
                                       placeholder="SSH password">
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Remote SQLite:</strong> Connects to a SQLite database file on a remote server via SSH/SCP.
                        </div>
                    </div>

                    <!-- MySQL Fields -->
                    <div id="remote_mysql_fields" class="connection-fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="mysql_host" class="form-label">MySQL Host</label>
                                <input type="text" class="form-control" id="mysql_host" name="mysql_host" 
                                       value="<?php echo htmlspecialchars($currentConfig['host'] ?? ''); ?>" 
                                       placeholder="localhost or mysql.example.com">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="mysql_port" class="form-label">Port</label>
                                <input type="number" class="form-control" id="mysql_port" name="mysql_port" 
                                       value="<?php echo htmlspecialchars($currentConfig['port'] ?? '3306'); ?>" 
                                       placeholder="3306">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="mysql_database" class="form-label">Database Name</label>
                                <input type="text" class="form-control" id="mysql_database" name="mysql_database" 
                                       value="<?php echo htmlspecialchars($currentConfig['database'] ?? ''); ?>" 
                                       placeholder="pcf_database">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="mysql_username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="mysql_username" name="mysql_username" 
                                       value="<?php echo htmlspecialchars($currentConfig['username'] ?? ''); ?>" 
                                       placeholder="mysql_user">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="mysql_password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="mysql_password" name="mysql_password" 
                                   value="<?php echo htmlspecialchars($currentConfig['password'] ?? ''); ?>" 
                                   placeholder="mysql_password">
                        </div>
                    </div>

                    <!-- PostgreSQL Fields -->
                    <div id="remote_postgresql_fields" class="connection-fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="pgsql_host" class="form-label">PostgreSQL Host</label>
                                <input type="text" class="form-control" id="pgsql_host" name="pgsql_host" 
                                       value="<?php echo htmlspecialchars($currentConfig['host'] ?? ''); ?>" 
                                       placeholder="localhost or postgres.example.com">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="pgsql_port" class="form-label">Port</label>
                                <input type="number" class="form-control" id="pgsql_port" name="pgsql_port" 
                                       value="<?php echo htmlspecialchars($currentConfig['port'] ?? '5432'); ?>" 
                                       placeholder="5432">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="pgsql_database" class="form-label">Database Name</label>
                                <input type="text" class="form-control" id="pgsql_database" name="pgsql_database" 
                                       value="<?php echo htmlspecialchars($currentConfig['database'] ?? ''); ?>" 
                                       placeholder="pcf_database">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="pgsql_username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="pgsql_username" name="pgsql_username" 
                                       value="<?php echo htmlspecialchars($currentConfig['username'] ?? ''); ?>" 
                                       placeholder="postgres_user">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="pgsql_password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="pgsql_password" name="pgsql_password" 
                                   value="<?php echo htmlspecialchars($currentConfig['password'] ?? ''); ?>" 
                                   placeholder="postgres_password">
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" name="test_connection" class="btn btn-warning">
                            <i class="fas fa-plug"></i> Test Connection
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Configuration
                        </button>
                        <a href="setup_pcf_integration.php" class="btn btn-success" target="_blank">
                            <i class="fas fa-play"></i> Run PCF Setup
                        </a>
                        <a href="install_pcf_integration.php" class="btn btn-info" target="_blank">
                            <i class="fas fa-download"></i> Run Full Installation
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <script>
        function toggleConnectionFields() {
            const connectionType = document.getElementById('connection_type').value;
            const allFields = document.querySelectorAll('.connection-fields');
            
            // Hide all field groups
            allFields.forEach(field => {
                field.style.display = 'none';
            });
            
            // Show the selected field group
            const selectedFields = document.getElementById(connectionType + '_fields');
            if (selectedFields) {
                selectedFields.style.display = 'block';
            }
        }
        
        // Handle test connection button
        function handleTestConnection() {
            const testBtn = document.querySelector('button[name="test_connection"]');
            const originalText = testBtn.innerHTML;
            
            // Show loading state
            testBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
            testBtn.disabled = true;
            
            // Re-enable button after form submission (in case of errors)
            setTimeout(() => {
                testBtn.innerHTML = originalText;
                testBtn.disabled = false;
            }, 10000); // 10 second timeout
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleConnectionFields();
            
            // Add event listener to test button
            const testBtn = document.querySelector('button[name="test_connection"]');
            if (testBtn) {
                testBtn.addEventListener('click', handleTestConnection);
            }
        });
        </script>

        <div class="card mt-4">
            <div class="card-header">
                <h5><i class="fas fa-info-circle"></i> Configuration Guide</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary">Setup Steps:</h6>
                        <ol>
                            <li><strong>Select Connection Type</strong> - Choose how you want to connect to PCF</li>
                            <li><strong>Fill Required Fields</strong> - Form fields will change based on your selection</li>
                            <li><strong>Test Connection</strong> - Verify your credentials work before saving</li>
                            <li><strong>Update Configuration</strong> - Save your settings</li>
                            <li><strong>Run Setup</strong> - Use "PCF Setup" or "Full Installation"</li>
                        </ol>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-info">Connection Types:</h6>
                        <ul>
                            <li><strong>Local SQLite:</strong> Database file on same server</li>
                            <li><strong>Remote SQLite:</strong> Database file on remote server (SSH)</li>
                            <li><strong>Remote MySQL:</strong> MySQL database connection</li>
                            <li><strong>Remote PostgreSQL:</strong> PostgreSQL database connection</li>
                        </ul>
                    </div>
                </div>
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle"></i>
                    <strong>Tips:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Form fields automatically change when you select a different connection type</li>
                        <li>Use the <strong>"Test Connection"</strong> button to verify your credentials before saving</li>
                        <li>For Remote SQLite, SSH2 extension is required for full testing</li>
                        <li>Make sure to fill in all required fields for your chosen connection type</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <script src="scripts/bootstrap.bundle.min.js"></script>
</body>
</html>