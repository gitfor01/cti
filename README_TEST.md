# 🔒 Tenable Integration Complete Test Suite

## Quick Start

### Easiest Way (Recommended)
```bash
./run_test.sh
```
This will automatically:
- Start a PHP server on port 8080
- Open the test in your browser
- Show you all test results

### Manual Way
```bash
cd "/Users/ammarfahad/Downloads/Others/CTI Proj/CTI Files/CTI-main"
php -S localhost:8080
```
Then open: http://localhost:8080/test_tenable_complete.php

---

## What This Test Does

### 🎯 Complete Integration Validation

This test script validates **EVERY** part of your Tenable Security Center integration:

#### 1️⃣ Connection & Authentication
- ✅ Tests API connectivity
- ✅ Validates credentials
- ✅ Measures response time
- ✅ Checks HTTP status codes

#### 2️⃣ New Vulnerabilities Query
- ✅ Critical severity (last 30 days)
- ✅ High severity (last 30 days)
- ✅ Medium severity (last 30 days)
- ✅ Low severity (last 30 days)

#### 3️⃣ Closed Vulnerabilities Query
- ✅ Critical severity (last 30 days)
- ✅ High severity (last 30 days)
- ✅ Medium severity (last 30 days)
- ✅ Low severity (last 30 days)

#### 4️⃣ Current Vulnerabilities & VGI Methods
Tests all three optimization methods:

**⚡ Method 1: sumid with count field (FASTEST)**
- Minimal API calls
- Best performance
- Preferred method

**🚀 Method 2: bulk export (RECOMMENDED)**
- Moderate API calls
- Good reliability
- Fallback option

**🐌 Method 3: individual queries (SLOWEST)**
- Many API calls
- Most reliable
- Last resort fallback

#### 5️⃣ VGI Calculation
- ✅ Validates formula: (Critical×4 + High×3 + Medium×2 + Low×1) / 100
- ✅ Shows detailed breakdown
- ✅ Verifies accuracy

#### 6️⃣ Performance Analysis
- ✅ Query times per severity
- ✅ API calls count
- ✅ Method efficiency comparison
- ✅ Performance recommendations

---

## Test Results Interpretation

### ✅ Success (Green)
- Test passed successfully
- Integration working correctly
- Safe to proceed

### ❌ Failed (Red)
- Test failed
- Needs attention
- Review error details

### ⚠️ Warning (Yellow)
- Test passed with warnings
- May need optimization
- Review recommendations

### ℹ️ Info (Blue)
- Informational message
- No action required
- Additional context

---

## What You'll See

### Summary Dashboard
```
┌─────────────────────────────────────┐
│  Tests Passed:        15            │
│  Tests Failed:         0            │
│  New Vulnerabilities:  234          │
│  Closed Vulnerabilities: 156        │
│  Current VGI Score:    45.67        │
│  Total Query Time:     2,345ms      │
└─────────────────────────────────────┘
```

### Performance Table
```
┌──────────┬─────────────────────┬──────────┬───────────┬──────────────────┐
│ Severity │ Method Used         │ Time(ms) │ API Calls │ Asset Instances  │
├──────────┼─────────────────────┼──────────┼───────────┼──────────────────┤
│ Critical │ sumid_count_field   │ 234ms    │ 1         │ 45               │
│ High     │ sumid_count_field   │ 456ms    │ 1         │ 123              │
│ Medium   │ bulk_export         │ 2,345ms  │ 5         │ 567              │
│ Low      │ bulk_export         │ 3,456ms  │ 8         │ 890              │
└──────────┴─────────────────────┴──────────┴───────────┴──────────────────┘
```

### Final Recommendation
If all tests pass:
```
✅ All Tests Passed!

Your Tenable Security Center integration is working perfectly.

You can now safely run va_demo.php at:
http://localhost:8080/va_demo.php
```

---

## Files Created

1. **test_tenable_complete.php** - Main test script
2. **run_test.sh** - Launcher script (easiest way to run)
3. **TEST_INSTRUCTIONS.md** - Detailed instructions
4. **README_TEST.md** - This file (quick reference)

---

## Troubleshooting

### Problem: Connection fails
**Solution:**
- Verify Tenable SC host URL (must include https://)
- Check network connectivity
- Ensure firewall allows HTTPS

### Problem: Authentication fails (401/403)
**Solution:**
- Double-check Access Key
- Double-check Secret Key
- Verify API key permissions in Tenable SC

### Problem: Timeout errors
**Solution:**
- Script allows 10 minutes
- Large datasets may take longer
- Run during off-peak hours

### Problem: Method fallback to Method 3
**Solution:**
- This is normal behavior
- Method 3 is slower but reliable
- Results will still be accurate

---

## Performance Expectations

| Method | Speed | API Calls | Best For |
|--------|-------|-----------|----------|
| Method 1 | ⚡ < 5s | 1-2 | Small to medium datasets |
| Method 2 | 🚀 30-60s | 5-20 | Medium to large datasets |
| Method 3 | 🐌 2-10min | 100+ | Very large datasets |

---

## Next Steps

### After All Tests Pass ✅

1. **Run the main application:**
   ```
   http://localhost:8080/va_demo.php
   ```

2. **Enter your credentials** (same as test)

3. **Select analysis period** (e.g., last 6 months)

4. **View your vulnerability dashboard** with:
   - Monthly trends
   - VGI scores
   - New vs closed vulnerabilities
   - Severity breakdowns

### If Tests Fail ❌

1. Review specific error messages in test output
2. Check TEST_INSTRUCTIONS.md for detailed troubleshooting
3. Verify API key permissions in Tenable SC
4. Contact Tenable support if needed

---

## Technical Notes

- **Test Duration:** 2-10 minutes (depending on data volume)
- **API Calls:** ~20-50 calls total
- **Data Range:** Last 30 days
- **Timeout:** 10 minutes maximum
- **Real-time Output:** Yes, shows progress as it runs

---

## Support

Need help? Check these resources:

1. **TEST_INSTRUCTIONS.md** - Detailed documentation
2. **Browser Console** - JavaScript errors
3. **PHP Error Logs** - Server-side errors
4. **Tenable SC API Docs** - API reference

---

**Status:** ✅ Ready to use
**Version:** 1.0
**Last Updated:** 2025

---

## Quick Command Reference

```bash
# Make launcher executable (first time only)
chmod +x run_test.sh

# Run the test (easiest way)
./run_test.sh

# Or manually start server
php -S localhost:8080

# Then open in browser
open http://localhost:8080/test_tenable_complete.php
```

---

**Remember:** This test must pass before running va_demo.php!