# VGI Optimization Methods Test Script

## Overview
This script tests which VGI (Vulnerability Generic Index) calculation method works with your Tenable Security Center instance.

## File Location
`test_vgi_methods.php`

## What It Tests

### Method 1: sumid with count field (Fastest)
- **Tool:** `sumid`
- **Speed:** ⚡ 1-5 seconds
- **API Calls:** 1-2 calls
- **How it works:** Uses aggregated count field directly from sumid query
- **Compatibility:** May not be available on all Tenable SC versions

### Method 2: Bulk export with aggregation (Recommended)
- **Tool:** `vulndetails`
- **Speed:** 🚀 10-30 seconds
- **API Calls:** 5-20 calls
- **How it works:** Exports vulnerability details in bulk and aggregates by plugin ID + IP
- **Compatibility:** Usually available on most Tenable SC versions

### Method 3: Individual queries (Slowest Fallback)
- **Tool:** `sumid` + `vulnipdetail`
- **Speed:** 🐌 60-300 seconds
- **API Calls:** 100+ calls
- **How it works:** Queries each vulnerability individually to count affected assets
- **Compatibility:** Always works (most compatible)

## How to Use

### Step 1: Access the Script
Open your browser and navigate to:
```
http://your-server/test_vgi_methods.php
```

### Step 2: Enter Credentials
Fill in the form with:
- **Tenable SC Host URL:** Your Tenable Security Center URL (e.g., `https://tenable-sc.example.com`)
- **Access Key:** Your API access key
- **Secret Key:** Your API secret key
- **Test Severity Level:** Choose which severity to test (High is recommended)

### Step 3: Run the Test
Click "🚀 Test VGI Methods" button

### Step 4: Review Results
The script will test all three methods and show:
- ✅ **WORKS** - Method is available and functional
- ❌ **NOT AVAILABLE** - Method doesn't work with your Tenable SC
- Execution time for each method
- Number of asset instances found
- Number of API calls made
- Performance comparison

## Understanding the Results

### If Method 1 Works (Best Case)
```
✅ Method 1: WORKS
⚡ Execution Time: 2.5 seconds
🎯 Recommendation: USE THIS METHOD (Fastest!)
```
Your Tenable SC supports the fastest method. The system will automatically use this.

### If Only Method 2 Works (Good Case)
```
❌ Method 1: NOT AVAILABLE
✅ Method 2: WORKS
🚀 Execution Time: 15 seconds
🎯 Recommendation: Use bulk export method
```
Your Tenable SC will use the bulk export method, which is still quite fast.

### If Only Method 3 Works (Acceptable Case)
```
❌ Method 1: NOT AVAILABLE
❌ Method 2: NOT AVAILABLE
✅ Method 3: WORKS
🐌 Execution Time: 45 seconds (limited test)
⚠️ Recommendation: Use only as last resort
```
Your Tenable SC will use individual queries. Expect slower performance on full scans.

## What Happens After Testing?

The VA Dashboard (`va_api.php`) automatically uses a **three-tier fallback approach**:

1. **First:** Tries Method 1 (fastest)
2. **If fails:** Tries Method 2 (recommended)
3. **If fails:** Falls back to Method 3 (slowest)

You don't need to configure anything - the system automatically detects and uses the best available method!

## Performance Impact

### For a typical monthly analysis (4 severities):

| Method | Time per Severity | Total Time (4 severities) | API Calls |
|--------|------------------|---------------------------|-----------|
| Method 1 | 2-5s | 8-20s | 4-8 |
| Method 2 | 10-30s | 40-120s | 20-80 |
| Method 3 | 60-300s | 240-1200s (4-20 min) | 400+ |

## Troubleshooting

### All Methods Failed
- Check your API credentials
- Verify network connectivity to Tenable SC
- Ensure your API keys have proper permissions
- Check Tenable SC API access is enabled

### Method 1 Not Available
This is normal for some Tenable SC versions. The `count` field in sumid responses may not be available in your version.

### Slow Performance
- If only Method 3 works, consider:
  - Limiting the time range for analysis
  - Analyzing fewer months at once
  - Scheduling analysis during off-peak hours

## Technical Details

### API Endpoints Used
All methods use: `/rest/analysis`

### Query Tools
- **Method 1:** `sumid` with count aggregation
- **Method 2:** `vulndetails` with bulk export
- **Method 3:** `sumid` + `vulnipdetail` individual queries

### Filters Applied
- `lastSeen`: Last 30 days
- `severity`: Selected severity level (Critical/High/Medium/Low)

## Notes

- The test is **non-destructive** - it only reads data
- Method 3 is tested with only 10 vulnerabilities to save time
- Actual performance may vary based on:
  - Number of vulnerabilities in your environment
  - Tenable SC server performance
  - Network latency
  - Time range being analyzed

## Support

If you encounter issues:
1. Check the error messages in the test results
2. Verify your Tenable SC version and API compatibility
3. Review Tenable SC API documentation for your version
4. Check server logs for detailed error information