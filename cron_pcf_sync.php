<?php
/**
 * PCF Sync Cron Job
 * 
 * This script should be run hourly via cron to automatically sync PCF findings
 * Add to crontab: 0 * * * * /usr/bin/php /path/to/cron_pcf_sync.php
 */

// Set the working directory to the script's directory
chdir(dirname(__FILE__));

require_once 'config/database.php';
require_once 'includes/pcf_functions.php';

// Log function
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message\n";
    file_put_contents('pcf_sync.log', $logEntry, FILE_APPEND | LOCK_EX);
    echo $logEntry;
}

try {
    logMessage("Starting PCF sync cron job");
    
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
            logMessage("Last sync was " . round($hoursSinceLastSync * 60, 1) . " minutes ago, skipping sync");
        }
    }
    
    if ($needsSync) {
        $syncResult = syncPcfFindings($pdo);
        
        if ($syncResult['success']) {
            logMessage("Sync completed successfully - {$syncResult['inserted']} new, {$syncResult['updated']} updated, {$syncResult['deleted']} deleted");
        } else {
            logMessage("Sync failed: {$syncResult['error']}");
        }
    }
    
    logMessage("PCF sync cron job completed");
    
} catch (Exception $e) {
    logMessage("PCF sync cron job failed: " . $e->getMessage());
}
?>