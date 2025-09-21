# PCF Integration Setup Instructions

## Quick Start (3 Steps)

### Step 1: Run the Installer
Visit this URL in your browser:
```
http://your-cti-domain/install_pcf_integration.php
```

This will:
- ✅ Check all prerequisites
- ✅ Create necessary database tables
- ✅ Perform initial sync from PCF
- ✅ Verify the installation

### Step 2: Access the PCF Dashboard
After successful installation, you can access the PCF Dashboard via:
- Click "PCF Dashboard" in the navigation menu
- Or visit: `http://your-cti-domain/pcf_dashboard.php`

### Step 3: Set Up Automatic Sync (Optional)
Add this line to your crontab for hourly automatic sync:
```bash
0 * * * * /usr/bin/php /path/to/your/cti/cron_pcf_sync.php
```

## What You Get

### 🎯 **PCF Dashboard Features:**
- **All PCF findings** displayed with color-coded severity levels
- **Advanced filtering** by project, severity, and status
- **Detailed finding views** with complete technical information
- **Statistics overview** showing counts by severity
- **Manual sync button** for on-demand updates
- **Pagination** for large datasets (50 findings per page)

### 📊 **Severity Color Coding:**
- **Critical (9.0-10.0)**: Red background
- **High (7.0-8.9)**: Orange background
- **Medium (4.0-6.9)**: Yellow background
- **Low (0.1-3.9)**: Green background
- **Info (0.0)**: Blue background

### 🔄 **Synchronization:**
- **Hourly automatic sync** (if cron is set up)
- **Manual sync** via dashboard button
- **Complete data sync** including:
  - Finding names and descriptions
  - CVSS scores and severity levels
  - CWE/CVE identifiers
  - Project information
  - Technical details and remediation steps
  - Creation dates and status

### 📈 **Dashboard Integration:**
- **PCF status widget** on main CTI dashboard
- **Quick statistics** showing critical/high findings
- **Sync status indicator** with last sync time
- **Direct links** to PCF dashboard

## Files Created

### Core Files:
- `pcf_dashboard.php` - Main PCF dashboard
- `pcf_finding_detail.php` - Detailed finding view
- `includes/pcf_functions.php` - PCF integration functions
- `includes/pcf_widget.php` - Status widget for main dashboard

### Setup & Maintenance:
- `install_pcf_integration.php` - One-click installer
- `setup_pcf_integration.php` - Alternative setup script
- `test_pcf_integration.php` - Integration testing
- `cron_pcf_sync.php` - Automatic sync script

### Documentation:
- `PCF_INTEGRATION_README.md` - Detailed documentation
- `SETUP_INSTRUCTIONS.md` - This file

## Database Tables Created

### `pcf_findings`
Stores all synchronized PCF findings with complete details including:
- Finding information (name, description, CVSS, CWE, CVE)
- Project context (name, description, dates)
- Technical details (remediation, risks, references)
- Sync timestamps

### `pcf_sync_log`
Tracks synchronization history with:
- Sync timestamps
- Number of findings synced
- Success/error status
- Detailed messages

## Troubleshooting

### If Installation Fails:
1. **Run the test script**: `http://your-cti-domain/test_pcf_integration.php`
2. **Check database connectivity** to both CTI and PCF databases
3. **Verify file permissions** on PCF database file
4. **Ensure PCF is running** and accessible

### If Sync Issues Occur:
1. **Check sync logs**: Look at `pcf_sync.log` file
2. **Test manual sync**: Use the "Sync PCF Data" button
3. **Verify PCF database**: Ensure it contains data
4. **Check file paths**: Verify paths in `pcf_functions.php`

### Common Issues:
- **"PCF database not found"**: Update the path in `pcf_functions.php`
- **"Permission denied"**: Ensure web server can read PCF database
- **"No findings synced"**: Check if PCF database contains Issues
- **"Table doesn't exist"**: Run the installer again

## Support

The integration includes comprehensive error handling and logging. If you encounter issues:

1. **Check the installer output** for specific error messages
2. **Run the test script** for detailed diagnostics
3. **Review sync logs** for synchronization issues
4. **Verify prerequisites** (database connections, file permissions)

## Next Steps

After successful installation:
1. **Explore the PCF Dashboard** to see all your findings
2. **Set up automatic sync** for regular updates
3. **Use filters** to focus on specific projects or severity levels
4. **Click on findings** to see detailed technical information
5. **Monitor the status widget** on your main dashboard

The integration is now ready to provide you with a unified view of all your PCF security findings within your CTI Tracker!