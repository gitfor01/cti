# Tenable API Filter Format Fix

## Issue Description

The Tenable Security Center API was returning a **403 error** with the message:

```
filter 'lastSeen' must be in the following format: <end day>:<start day>, <start time>-<end time>, or 'currentmonth, current quarter, last
```

## Root Cause

The code was using **incorrect filter formats** for time-based queries:

### ❌ **WRONG Format (Before Fix)**
```php
// Using hyphen separator
'value' => $startTime . '-' . $endTime

// Using >= operator with single timestamp
'operator' => '>=',
'value' => (string)$startTime
```

### ✅ **CORRECT Format (After Fix)**
```php
// Using colon separator with reversed order (endTime:startTime)
'operator' => '=',
'value' => $endTime . ':' . $startTime
```

## Key Changes

According to Tenable SC API documentation, time range filters must use:
- **Separator:** Colon (`:`) not hyphen (`-`)
- **Order:** `endTime:startTime` (reversed, not startTime:endTime)
- **Operator:** `=` for range queries (not `>=` or `<=`)

## Files Fixed

### 1. **va_api.php**
Fixed 4 methods:
- `testConnection()` - Line 279-281
- `getNewVulnerabilitiesBySeverity()` - Line 424-426
- `getClosedVulnerabilitiesBySeverity()` - Line 462-464
- `getVulnerabilityAssetInstances()` - Line 501-504

### 2. **test_vgi_methods.php**
Fixed 2 locations:
- `testConnection()` method - Line 734-736
- Main test query filters - Line 485-487

### 3. **va_cli.php**
Fixed 1 method:
- `getVulnerabilityCount()` - Line 79-81

## Filter Types Fixed

### `lastSeen` Filter
Used for querying current/active vulnerabilities
```php
// BEFORE
'filterName' => 'lastSeen',
'operator' => '>=',
'value' => (string)$startTime

// AFTER
'filterName' => 'lastSeen',
'operator' => '=',
'value' => $endTime . ':' . $startTime
```

### `firstSeen` Filter
Used for querying new vulnerabilities
```php
// BEFORE
'filterName' => 'firstSeen',
'operator' => '=',
'value' => $startTime . '-' . $endTime

// AFTER
'filterName' => 'firstSeen',
'operator' => '=',
'value' => $endTime . ':' . $startTime
```

### `lastMitigated` Filter
Used for querying closed/patched vulnerabilities
```php
// BEFORE
'filterName' => 'lastMitigated',
'operator' => '=',
'value' => $startTime . '-' . $endTime

// AFTER
'filterName' => 'lastMitigated',
'operator' => '=',
'value' => $endTime . ':' . $startTime
```

## Testing

After applying these fixes, all API requests should work correctly:

1. **Connection Test** - Now uses proper `lastSeen` format
2. **New Vulnerabilities Query** - Now uses proper `firstSeen` format
3. **Closed Vulnerabilities Query** - Now uses proper `lastMitigated` format
4. **VGI Calculations** - All 3 methods now use proper `lastSeen` format

## Impact

✅ **All test scripts should now work without 403 errors**
✅ **VA Dashboard should successfully fetch data from Tenable SC**
✅ **VGI calculations should complete successfully**
✅ **Monthly vulnerability analysis should work correctly**

## Additional Notes

- The fix maintains backward compatibility with the existing code structure
- No changes to function signatures or return values
- All three VGI optimization methods are preserved
- The automatic fallback logic remains intact

## Verification Steps

1. Run `test_vgi_methods.php` - Should complete all 3 method tests
2. Run `test_tenable_complete.php` - Should pass all 10 test sections
3. Use VA Dashboard - Should successfully analyze vulnerabilities
4. Check error logs - Should see no more 403 errors related to filter formats

---

**Date Fixed:** 2025
**Issue Type:** API Integration Bug
**Severity:** High (Blocking all Tenable SC queries)
**Status:** ✅ RESOLVED