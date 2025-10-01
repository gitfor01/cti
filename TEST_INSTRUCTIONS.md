# Tenable Integration Test Instructions

## Overview
A comprehensive test script has been created to validate ALL parts of your Tenable Security Center integration before running the main va_demo.php application.

## Test Script Location
**File:** `test_tenable_complete.php`

## How to Run

### Option 1: Using PHP Built-in Server
```bash
cd "/Users/ammarfahad/Downloads/Others/CTI Proj/CTI Files/CTI-main"
php -S localhost:8080
```

Then open in your browser:
```
http://localhost:8080/test_tenable_complete.php
```

### Option 2: Using Existing Server
If you already have a web server running on port 8080, simply navigate to:
```
http://localhost:8080/test_tenable_complete.php
```

## What Gets Tested

### ✅ Test 1: Connection & Authentication
- Validates API connectivity
- Tests authentication with your credentials
- Measures response time
- Verifies HTTP status codes

### ✅ Test 2: New Vulnerabilities Query
Tests the ability to query new vulnerabilities for:
- Critical severity (last 30 days)
- High severity (last 30 days)
- Medium severity (last 30 days)
- Low severity (last 30 days)

### ✅ Test 3: Closed Vulnerabilities Query
Tests the ability to query closed/mitigated vulnerabilities for:
- Critical severity (last 30 days)
- High severity (last 30 days)
- Medium severity (last 30 days)
- Low severity (last 30 days)

### ✅ Test 4: Current Vulnerabilities & VGI Methods
Tests all three VGI calculation optimization methods:

**Method 1: sumid with count field (FASTEST)**
- Uses sumid tool with count aggregation
- Minimal API calls
- Best performance

**Method 2: bulk export (RECOMMENDED)**
- Exports vulnerability details in bulk
- Aggregates asset instances
- Good balance of speed and reliability

**Method 3: individual queries (FALLBACK)**
- Queries each vulnerability individually
- Most API calls
- Slowest but most reliable

### ✅ Test 5: VGI Calculation
Validates the Vulnerability Generic Index calculation:
- Critical instances × 4
- High instances × 3
- Medium instances × 2
- Low instances × 1
- Total Score / 100 = VGI

### ✅ Test 6: Performance Analysis
- Measures query times for each severity
- Counts API calls per method
- Compares method efficiency
- Provides performance recommendations

## Expected Results

### Success Indicators
✅ All tests show "PASSED" status
✅ Connection test returns HTTP 200
✅ Vulnerability counts are retrieved successfully
✅ VGI calculation completes without errors
✅ Performance metrics are displayed

### What to Look For
- **Green badges** = Tests passed
- **Red badges** = Tests failed (needs attention)
- **Yellow badges** = Warnings (may need review)
- **Blue badges** = Informational

## Test Output Includes

1. **Summary Cards**
   - Total tests passed/failed
   - New vulnerabilities count
   - Closed vulnerabilities count
   - Current VGI score
   - Total query time

2. **Performance Table**
   - Method used for each severity
   - Query time in milliseconds
   - Number of API calls
   - Asset instances found

3. **Recommendations**
   - If all tests pass: Safe to proceed with va_demo.php
   - If tests fail: Specific issues to resolve

## Troubleshooting

### Connection Fails
- Verify Tenable SC host URL is correct (include https://)
- Check network connectivity
- Ensure firewall allows outbound HTTPS

### Authentication Fails (HTTP 401/403)
- Verify Access Key is correct
- Verify Secret Key is correct
- Check API key permissions in Tenable SC

### Timeout Errors
- Script allows up to 10 minutes
- Large datasets may take longer
- Consider running during off-peak hours

### Method Fallback
- If Method 1 fails, Method 2 is tried automatically
- If Method 2 fails, Method 3 is used as fallback
- All methods should produce accurate results

## After Testing

### If All Tests Pass ✅
You can safely run your main application:
```
http://localhost:8080/va_demo.php
```

### If Tests Fail ❌
1. Review the specific error messages
2. Check Tenable SC API documentation
3. Verify your API key has sufficient permissions
4. Contact Tenable support if needed

## Technical Details

### API Endpoints Tested
- `/rest/analysis` - Main query endpoint
- Tool types: `listvuln`, `sumid`, `sumip`, `vulndetails`
- Source types: `cumulative`, `patched`

### Filters Used
- `firstSeen` - For new vulnerabilities
- `lastMitigated` - For closed vulnerabilities
- `lastSeen` - For current vulnerabilities
- `severity` - For severity filtering
- `pluginID` - For specific vulnerability queries

### Performance Expectations
- **Method 1 (sumid):** < 5 seconds per severity
- **Method 2 (bulk export):** 30-60 seconds per severity
- **Method 3 (individual):** 2-10 minutes per severity

## Notes

- The test uses the last 30 days of data for queries
- Real-time output shows progress as tests run
- All API calls are logged for debugging
- Test results are displayed in a user-friendly format

## Support

If you encounter issues:
1. Check the browser console for JavaScript errors
2. Review PHP error logs
3. Verify Tenable SC API is accessible
4. Ensure all required PHP extensions are installed (curl, json)

---

**Created:** 2025
**Purpose:** Pre-flight validation for Tenable SC integration
**Status:** Ready to use