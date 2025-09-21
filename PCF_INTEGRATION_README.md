# PCF Integration for CTI Tracker

This integration allows the CTI Tracker to synchronize and display security findings from the PCF (PenTest Collaboration Framework) project.

## Features

- **Real-time Dashboard**: View all PCF security findings in a unified dashboard
- **Severity Classification**: Findings are color-coded by CVSS score (Critical, High, Medium, Low, Info)
- **Project Filtering**: Filter findings by PCF project
- **Detailed Views**: Click on any finding to see complete technical details
- **Automatic Sync**: Hourly synchronization with manual sync option
- **Statistics**: Overview cards showing finding counts by severity

## Setup Instructions

### 1. Initial Setup

1. Run the setup script to create necessary database tables:
   ```
   http://your-cti-domain/setup_pcf_integration.php
   ```

2. This will:
   - Create the `pcf_findings` table
   - Create the `pcf_sync_log` table
   - Test the PCF database connection
   - Perform an initial sync of all PCF findings

### 2. Database Configuration

The integration assumes:
- PCF database is located at: `/Users/ammarfahad/Downloads/Others/CTI Proj/pcf/configuration/database.sqlite3`
- PCF uses SQLite database (default configuration)

If your PCF installation is different, update the path in `includes/pcf_functions.php`:
```php
$pcfDbPath = '/path/to/your/pcf/configuration/database.sqlite3';
```

### 3. Automatic Synchronization (Optional)

To enable hourly automatic synchronization, add this to your crontab:
```bash
0 * * * * /usr/bin/php /path/to/your/cti/cron_pcf_sync.php
```

This will:
- Check if the last sync was more than 1 hour ago
- Automatically sync new findings from PCF
- Log all sync activities to `pcf_sync.log`

### 4. Manual Synchronization

You can manually sync at any time by:
- Clicking the "Sync PCF Data" button on the PCF Dashboard
- Running the cron script manually: `php cron_pcf_sync.php`

## Usage

### Accessing the PCF Dashboard

1. Log into CTI Tracker
2. Click "PCF Dashboard" in the navigation menu
3. View all synchronized PCF findings

### Filtering Findings

Use the filter section to narrow down findings by:
- **Project**: Select a specific PCF project
- **Severity**: Filter by CVSS score ranges
- **Status**: Filter by finding status (open, fixed, closed, etc.)

### Viewing Finding Details

Click on any finding name to see:
- Complete description and technical details
- Risk assessment and remediation steps
- Project information and timestamps
- CWE/CVE information if available

## Database Schema

### pcf_findings Table

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Auto-increment primary key |
| pcf_id | VARCHAR(255) | Original PCF finding ID |
| name | VARCHAR(500) | Finding name/title |
| description | TEXT | Detailed description |
| url_path | TEXT | Affected URL or path |
| cvss | DECIMAL(3,1) | CVSS score (0.0-10.0) |
| cwe | INT | CWE number |
| cve | VARCHAR(50) | CVE identifier |
| status | VARCHAR(50) | Finding status |
| project_id | VARCHAR(255) | PCF project ID |
| project_name | VARCHAR(255) | PCF project name |
| type | VARCHAR(50) | Finding type |
| fix_description | TEXT | Remediation steps |
| technical | TEXT | Technical details |
| risks | TEXT | Risk assessment |
| references | TEXT | External references |
| created_at | TIMESTAMP | Sync timestamp |
| updated_at | TIMESTAMP | Last update timestamp |

### pcf_sync_log Table

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Auto-increment primary key |
| sync_time | TIMESTAMP | When sync occurred |
| findings_count | INT | Number of findings synced |
| status | ENUM | 'success' or 'error' |
| message | TEXT | Sync details or error message |

## Severity Classification

Findings are classified based on CVSS scores:

- **Critical** (9.0-10.0): Red background
- **High** (7.0-8.9): Orange background  
- **Medium** (4.0-6.9): Yellow background
- **Low** (0.1-3.9): Green background
- **Info** (0.0): Blue background

## Troubleshooting

### PCF Database Connection Issues

1. Ensure PCF is running and the database file exists
2. Check file permissions on the PCF database
3. Verify the database path in `pcf_functions.php`
4. Check the setup script output for specific errors

### Sync Issues

1. Check `pcf_sync.log` for detailed error messages
2. Ensure the web server has read access to the PCF database
3. Verify database connectivity using the setup script

### Performance Considerations

- The sync process loads all PCF findings into memory
- For large PCF databases (>10,000 findings), consider implementing batch processing
- The dashboard uses pagination (50 findings per page) for better performance

## Files Added/Modified

### New Files
- `pcf_dashboard.php` - Main dashboard page
- `pcf_finding_detail.php` - Detailed finding view
- `includes/pcf_functions.php` - PCF integration functions
- `setup_pcf_integration.php` - Setup script
- `cron_pcf_sync.php` - Automatic sync script

### Modified Files
- `includes/header.php` - Added PCF Dashboard to navigation

## Security Notes

- The integration only reads from the PCF database (no write operations)
- All user inputs are sanitized and use prepared statements
- Authentication is required to access PCF dashboard pages
- Database connections use PDO with error handling

## Support

If you encounter issues:
1. Check the setup script output
2. Review `pcf_sync.log` for sync errors
3. Ensure proper file permissions and database connectivity
4. Verify PCF is running and accessible