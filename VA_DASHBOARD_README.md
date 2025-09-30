# VA Dashboard - Vulnerability Analysis

The VA Dashboard is a comprehensive vulnerability analysis tool that integrates with Tenable Security Center to provide beautiful visualizations and insights into your organization's vulnerability management trends.

## 🚀 Features

- **Interactive Visualizations**: Beautiful arc-based charts showing monthly vulnerability trends
- **Real-time Data**: Direct integration with Tenable Security Center API
- **Comprehensive Analytics**: Track new vulnerabilities, closed vulnerabilities, and net changes
- **Multiple Interfaces**: Web dashboard, demo version, and command-line interface
- **Responsive Design**: Works perfectly on desktop, tablet, and mobile devices
- **Status Indicators**: Clear visual indicators for vulnerability backlog trends

## 📁 Files Overview

### Web Interface
- **`va_dashboard.php`** - Main web dashboard with live Tenable SC integration
- **`va_demo.php`** - Demo version with sample data for testing and demonstration
- **`va_api.php`** - Backend API that handles Tenable SC communication

### Command Line Interface
- **`va_cli.php`** - Standalone command-line version with colored output

## 🔧 Setup Instructions

### Prerequisites
- PHP 7.4 or higher
- cURL extension enabled
- Valid Tenable Security Center instance
- API access keys for Tenable SC

### Configuration

#### For Web Interface (va_dashboard.php)
1. Access the VA Dashboard through the navigation menu
2. Enter your Tenable SC configuration:
   - **SC Host**: Your Tenable Security Center URL (e.g., `https://your-sc-instance.com`)
   - **Access Key**: Your API access key
   - **Secret Key**: Your API secret key
   - **Months to Analyze**: Number of months to analyze (6, 12, 18, or 24)

#### For Command Line Interface (va_cli.php)
1. Edit the configuration section at the top of `va_cli.php`:
   ```php
   $SC_HOST = 'https://your-sc-instance.com';
   $ACCESS_KEY = 'YOUR_ACCESS_KEY_HERE';
   $SECRET_KEY = 'YOUR_SECRET_KEY_HERE';
   $MONTHS_TO_ANALYZE = 12;
   ```

2. Run from command line:
   ```bash
   php va_cli.php
   ```

### Getting Tenable SC API Keys
1. Log into your Tenable Security Center
2. Navigate to **User Preferences** → **API Keys**
3. Generate new API keys
4. Copy the Access Key and Secret Key

## 🎨 Dashboard Features

### Interactive Visualization
- **Arc Chart**: Each month is represented by a colored arc
- **Hover Tooltips**: Detailed information on mouse hover
- **Color Coding**: Quarterly color schemes for easy identification
- **Responsive Canvas**: Automatically adjusts to screen size

### Statistics Cards
- **Total New Vulnerabilities**: Sum of all new vulnerabilities found
- **Total Closed Vulnerabilities**: Sum of all vulnerabilities remediated
- **Net Change**: Overall change in vulnerability count
- **Average per Month**: Average new vulnerabilities per month

### Status Indicators
- 🔴 **Growing**: Vulnerability backlog is increasing
- 🟢 **Decreasing**: Vulnerability backlog is decreasing  
- 🟡 **Stable**: Vulnerability backlog is stable

## 🔍 Data Analysis

The dashboard analyzes:
- **New Vulnerabilities**: Vulnerabilities first discovered in each month
- **Closed Vulnerabilities**: Vulnerabilities remediated/mitigated in each month
- **Net Change**: The difference between new and closed vulnerabilities

### Time Ranges
- **6 Months**: Quick recent trend analysis
- **12 Months**: Full year analysis (recommended)
- **18 Months**: Extended trend analysis
- **24 Months**: Long-term trend analysis

## 🎯 Use Cases

### Security Teams
- Track vulnerability management effectiveness
- Identify trends in vulnerability discovery and remediation
- Report on security posture improvements
- Plan resource allocation for vulnerability management

### Management Reporting
- Executive dashboards showing security trends
- Quarterly security reviews
- Budget planning for security tools and personnel
- Compliance reporting

### Operational Teams
- Monitor vulnerability scanning effectiveness
- Track patch management success rates
- Identify periods of high vulnerability activity
- Plan maintenance windows

## 🚨 Troubleshooting

### Common Issues

#### API Connection Errors
- Verify Tenable SC URL is correct and accessible
- Check API keys are valid and not expired
- Ensure network connectivity to Tenable SC
- Verify SSL certificates (disable SSL verification for testing only)

#### No Data Returned
- Check if vulnerabilities exist in the specified time range
- Verify user permissions in Tenable SC
- Ensure proper repository access in Tenable SC

#### Slow Performance
- Reduce the number of months to analyze
- Check Tenable SC system performance
- Verify network latency to Tenable SC

### Error Messages
- **"Unauthorized"**: Check API keys and user permissions
- **"Connection error"**: Network or SSL issues
- **"Invalid JSON response"**: Tenable SC API issues

## 🔒 Security Considerations

- API keys are transmitted securely via HTTPS
- Keys are not stored permanently on the server
- Session-based authentication required for web access
- SSL verification should be enabled in production

## 📊 Sample Output

### Web Dashboard
The web interface provides:
- Interactive arc visualization
- Real-time hover tooltips
- Responsive statistics cards
- Status indicators with icons

### Command Line Output
```
Tenable Security Center - Monthly Vulnerability Analysis
================================================================================

Month           New Vulnerabilities  Closed Vulnerabilities Net Change     
--------------------------------------------------------------------------------
Processing 2024-01... 45                   32                   +13           
Processing 2024-02... 38                   41                   -3            
Processing 2024-03... 52                   28                   +24           
...
--------------------------------------------------------------------------------
TOTAL           491                  456                  +35           

================================================================================
Analysis complete!

SUMMARY:
- Total new vulnerabilities: 491
- Total closed vulnerabilities: 456
- Net change: +35
- Status: ⚠️  Vulnerability backlog is growing
```

## 🔄 Integration with Existing System

The VA Dashboard integrates seamlessly with your existing security monitoring platform:
- Uses the same authentication system
- Follows the same UI/UX patterns
- Accessible through the main navigation menu
- Consistent with other dashboard components

## 📈 Future Enhancements

Potential future features:
- Vulnerability severity breakdown
- Asset group analysis
- Trend predictions
- Export capabilities (PDF, CSV)
- Email reporting
- Integration with ticketing systems
- Custom date range selection
- Vulnerability aging analysis

## 🆘 Support

For issues or questions:
1. Check the troubleshooting section above
2. Verify Tenable SC connectivity and permissions
3. Review PHP error logs
4. Test with the demo version first

## 📝 License

This VA Dashboard is part of the Assurance Monitoring Tool (AMT) project and follows the same licensing terms.