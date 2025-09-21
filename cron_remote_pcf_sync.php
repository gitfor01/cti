<?php
/**
 * Remote PCF Sync Cron Job
 * 
 * This script should be run hourly via cron to automatically sync remote PCF findings
 * Add to crontab: 0 * * * * /usr/bin/php /path/to/cron_remote_pcf_sync.php
 */

// Set the working directory to the script's directory
chdir(dirname(__FILE__));

require_once 'config/database.php';
require_once 'config/pcf_remote_config.php';
require_once 'includes/pcf_remote_functions.php';
require_once 'includes/pcf_functions.php'; // For getLastSyncTime function

// Log function
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message\n";
    
    // Log to both file and stdout
    $logFile = 'pcf_remote_sync.log';
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    echo $logEntry;
    
    // Also log to system log if available
    if (function_exists('syslog')) {
        syslog(LOG_INFO, "PCF Remote Sync: $message");
    }
}

// Error handler
function handleError($errno, $errstr, $errfile, $errline) {
    logMessage("PHP Error [$errno]: $errstr in $errfile on line $errline");
    return true;
}

// Exception handler
function handleException($exception) {
    logMessage("Uncaught Exception: " . $exception->getMessage());
    logMessage("Stack trace: " . $exception->getTraceAsString());
}

// Set error handlers
set_error_handler('handleError');
set_exception_handler('handleException');

try {
    logMessage("Starting remote PCF sync cron job");
    logMessage("Connection type: " . PCF_CONNECTION_TYPE);
    
    // Check if we need to sync (last sync was more than 1 hour ago)
    $lastSync = getLastSyncTime($pdo);
    $needsSync = false;
    
    if (!$lastSync) {
        logMessage("No previous sync found, performing initial sync");
        $needsSync = true;
    } else {
        $timeSinceLastSync = time() - strtotime($lastSync);
        $hoursSinceLastSync = $timeSinceLastSync / 3600;
        
        if ($hoursSinceLastSync >= 1) {
            logMessage("Last sync was " . round($hoursSinceLastSync, 2) . " hours ago, syncing now");
            $needsSync = true;
        } else {
            logMessage("Last sync was " . round($hoursSinceLastSync, 2) . " hours ago, skipping sync");
        }
    }
    
    if ($needsSync) {
        // Test connection first
        logMessage("Testing remote PCF connection...");
        $testResult = testRemotePcfConnection();
        
        if (!$testResult['connection_success']) {
            logMessage("Connection test failed: " . ($testResult['error'] ?? 'Unknown error'));
            exit(1);
        }
        
        logMessage("Connection test successful - Found {$testResult['issue_count']} issues, {$testResult['project_count']} projects");
        
        if ($testResult['issue_count'] == 0) {
            logMessage("Warning: No issues found in remote PCF database");
        }
        
        // Perform sync with retry logic
        $maxRetries = PCF_SYNC_MAX_RETRIES;
        $retryDelay = PCF_SYNC_RETRY_DELAY;
        $syncSuccess = false;
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                logMessage("Sync attempt $attempt of $maxRetries");
                
                $syncResult = syncRemotePcfFindings($pdo);
                
                if ($syncResult['success']) {
                    logMessage("Sync completed successfully: " . $syncResult['message']);
                    logMessage("Synced {$syncResult['count']} findings from remote PCF");
                    $syncSuccess = true;
                    break;
                } else {
                    logMessage("Sync attempt $attempt failed: " . $syncResult['error']);
                    
                    if ($attempt < $maxRetries) {
                        logMessage("Waiting {$retryDelay} seconds before retry...");
                        sleep($retryDelay);
                    }
                }
            } catch (Exception $e) {
                logMessage("Sync attempt $attempt threw exception: " . $e->getMessage());
                
                if ($attempt < $maxRetries) {
                    logMessage("Waiting {$retryDelay} seconds before retry...");
                    sleep($retryDelay);
                }
            }
        }
        
        if (!$syncSuccess) {
            logMessage("All sync attempts failed after $maxRetries retries");
            exit(1);
        }
        
        // Clean up old cache files if caching is enabled
        if (PCF_CACHE_ENABLED && is_dir(PCF_CACHE_DIR)) {
            $cacheFiles = glob(PCF_CACHE_DIR . '*.cache');
            $cleanedFiles = 0;
            
            foreach ($cacheFiles as $cacheFile) {
                if (filemtime($cacheFile) < (time() - PCF_CACHE_DURATION * 2)) {
                    unlink($cacheFile);
                    $cleanedFiles++;
                }
            }
            
            if ($cleanedFiles > 0) {
                logMessage("Cleaned up $cleanedFiles expired cache files");
            }
        }
        
        // Log memory usage
        $memoryUsage = memory_get_peak_usage(true);
        $memoryMB = round($memoryUsage / 1024 / 1024, 2);
        logMessage("Peak memory usage: {$memoryMB} MB");
        
    } else {
        logMessage("Sync not needed at this time");
    }
    
    logMessage("Remote PCF sync cron job completed successfully");
    
} catch (Exception $e) {
    logMessage("Fatal error in sync cron job: " . $e->getMessage());
    logMessage("Stack trace: " . $e->getTraceAsString());
    exit(1);
} catch (Error $e) {
    logMessage("Fatal PHP error in sync cron job: " . $e->getMessage());
    logMessage("Stack trace: " . $e->getTraceAsString());
    exit(1);
}

// Restore default error handlers
restore_error_handler();
restore_exception_handler();
?>