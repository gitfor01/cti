<?php
/**
 * PCF Remote Database Configuration
 * 
 * This file contains configuration for connecting to remote PCF databases
 * Supports multiple connection types: Local SQLite, Remote SQLite, MySQL, PostgreSQL, REST API
 */

// PCF Connection Type
// Options: 'local_sqlite', 'remote_sqlite', 'remote_mysql', 'remote_postgresql', 'remote_api'
define('PCF_CONNECTION_TYPE', 'local_sqlite');

// Local SQLite Configuration (default)
define('PCF_SQLITE_PATH', '/Users/ammarfahad/Downloads/Others/CTI Proj/pcf/configuration/database.sqlite3');

// Remote SQLite Configuration (SQLite file accessed over network/SSH)
define('PCF_REMOTE_SQLITE_HOST', 'your-pcf-server.com');
define('PCF_REMOTE_SQLITE_PATH', '/path/to/remote/pcf/database.sqlite3');
define('PCF_REMOTE_SQLITE_METHOD', 'ssh'); // Options: 'ssh', 'smb', 'ftp', 'http'
define('PCF_REMOTE_SQLITE_USERNAME', 'remote_user');
define('PCF_REMOTE_SQLITE_PASSWORD', 'remote_password');
define('PCF_REMOTE_SQLITE_PORT', 22); // SSH port, adjust for other methods
define('PCF_REMOTE_SQLITE_LOCAL_CACHE', '/tmp/pcf_remote_cache.sqlite3');
define('PCF_REMOTE_SQLITE_CACHE_DURATION', 300); // Cache for 5 minutes

// Remote MySQL Configuration
define('PCF_MYSQL_HOST', 'your-pcf-server.com');
define('PCF_MYSQL_PORT', 3306);
define('PCF_MYSQL_DATABASE', 'pcf_database');
define('PCF_MYSQL_USERNAME', 'pcf_user');
define('PCF_MYSQL_PASSWORD', 'pcf_password');

// Remote PostgreSQL Configuration
define('PCF_POSTGRESQL_HOST', 'your-pcf-server.com');
define('PCF_POSTGRESQL_PORT', 5432);
define('PCF_POSTGRESQL_DATABASE', 'pcf_database');
define('PCF_POSTGRESQL_USERNAME', 'pcf_user');
define('PCF_POSTGRESQL_PASSWORD', 'pcf_password');

// Remote API Configuration (for PCF with REST API)
define('PCF_API_BASE_URL', 'https://your-pcf-server.com/api');
define('PCF_API_TOKEN', 'your-api-token');
define('PCF_API_USERNAME', 'api_user');
define('PCF_API_PASSWORD', 'api_password');

// Connection timeout settings
define('PCF_CONNECTION_TIMEOUT', 30);
define('PCF_QUERY_TIMEOUT', 60);

// SSL/TLS settings for remote connections
define('PCF_USE_SSL', true);
define('PCF_VERIFY_SSL', true);
define('PCF_SSL_CERT_PATH', ''); // Path to SSL certificate if needed

// Sync settings
define('PCF_SYNC_BATCH_SIZE', 1000); // Number of records to process at once
define('PCF_SYNC_MAX_RETRIES', 3);
define('PCF_SYNC_RETRY_DELAY', 5); // seconds

// Cache settings for remote connections
define('PCF_CACHE_ENABLED', true);
define('PCF_CACHE_DURATION', 300); // 5 minutes in seconds
define('PCF_CACHE_DIR', __DIR__ . '/../cache/pcf/');

// Logging settings
define('PCF_LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('PCF_LOG_FILE', __DIR__ . '/../logs/pcf_remote.log');

// Database mapping for different PCF versions
// This allows compatibility with different PCF database schemas
$PCF_TABLE_MAPPING = [
    'issues' => 'Issues',           // PCF issues table name
    'projects' => 'Projects',       // PCF projects table name
    'users' => 'Users',            // PCF users table name (if available)
    'categories' => 'Categories'    // PCF categories table name (if available)
];

// Field mapping for different PCF versions
$PCF_FIELD_MAPPING = [
    'issue_id' => 'id',
    'issue_name' => 'name',
    'issue_description' => 'description',
    'issue_cvss' => 'cvss',
    'issue_cwe' => 'cwe',
    'issue_cve' => 'cve',
    'issue_status' => 'status',
    'issue_type' => 'type',
    'issue_fix' => 'fix',
    'issue_technical' => 'technical',
    'issue_risks' => 'risks',
    'issue_references' => 'references',
    'issue_url_path' => 'url_path',
    'issue_param' => 'param',
    'project_id' => 'project_id',
    'project_name' => 'name',
    'project_description' => 'description',
    'project_start_date' => 'start_date',
    'project_end_date' => 'end_date'
];

// Export configurations for use in other files
$GLOBALS['PCF_TABLE_MAPPING'] = $PCF_TABLE_MAPPING;
$GLOBALS['PCF_FIELD_MAPPING'] = $PCF_FIELD_MAPPING;
?>