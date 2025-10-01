# Tenable API Filter Format - CORRECTED

## Latest Error Message

```
end day must be later (i.e less than) than start day. 
filter 'lastSeen' must be in the following format: <end day>:<start day>
```

## Understanding the Error

Despite the confusing wording in the error message, what Tenable SC actually requires is:

**The first value (start) must be LESS THAN the second value (end)**

This means the format is: `startTime:endTime` where `startTime < endTime`

## Correct Format

### ✅ **CORRECT Implementation**

```php
$endTime = time();                      // Current time (e.g., 1735000000)
$startTime = $endTime - (30 * 86400);   // 30 days ago (e.g., 1732408000)

// Correct format: smaller value : larger value
'value' => $startTime . ':' . $endTime  // "1732408000:1735000000"
```

### ❌ **WRONG Implementation**

```php
// Wrong: larger value first
'value' => $endTime . ':' . $startTime  // "1735000000:1732408000" ❌

// Wrong: using hyphen
'value' => $startTime . '-' . $endTime  // "1732408000-1735000000" ❌

// Wrong: using >= operator
'operator' => '>=',
'value' => (string)$startTime           // ❌
```

## Why the Error Message is Confusing

The error says `<end day>:<start day>` but what it really means is:
- **First position:** The EARLIER timestamp (start of range)
- **Second position:** The LATER timestamp (end of range)

The terminology "end day" and "start day" refer to the position in the string, not the chronological order.

## Examples

### Example 1: Last 30 Days
```php
$now = 1735000000;           // Jan 1, 2025
$thirtyDaysAgo = 1732408000; // Dec 2, 2024

// Correct
'value' => '1732408000:1735000000'  // ✅ (smaller:larger)

// Wrong
'value' => '1735000000:1732408000'  // ❌ (larger:smaller)
```

### Example 2: Specific Month (December 2024)
```php
$startOfMonth = 1701388800;  // Dec 1, 2024 00:00:00
$endOfMonth = 1704067199;    // Dec 31, 2024 23:59:59

// Correct
'value' => '1701388800:1704067199'  // ✅ (smaller:larger)
```

## All Fixed Filters

### 1. `lastSeen` Filter (Current Vulnerabilities)
```php
$startTime = $endTime - (30 * 86400);
[
    'filterName' => 'lastSeen',
    'operator' => '=',
    'value' => $startTime . ':' . $endTime  // ✅ Correct
]
```

### 2. `firstSeen` Filter (New Vulnerabilities)
```php
[
    'filterName' => 'firstSeen',
    'operator' => '=',
    'value' => $startTime . ':' . $endTime  // ✅ Correct
]
```

### 3. `lastMitigated` Filter (Closed Vulnerabilities)
```php
[
    'filterName' => 'lastMitigated',
    'operator' => '=',
    'value' => $startTime . ':' . $endTime  // ✅ Correct
]
```

## Files Corrected

All files have been updated with the correct format:

1. ✅ **va_api.php** - All 4 methods corrected
2. ✅ **test_vgi_methods.php** - Both locations corrected
3. ✅ **va_cli.php** - Method corrected

## Quick Validation

To verify the format is correct, check:

```php
// This should ALWAYS be true
assert($startTime < $endTime);

// The format should be
$filter = $startTime . ':' . $endTime;

// Example output: "1732408000:1735000000"
// Where 1732408000 < 1735000000 ✅
```

## Summary

| Aspect | Correct Value |
|--------|---------------|
| **Separator** | Colon `:` |
| **Order** | `startTime:endTime` |
| **Operator** | `=` |
| **Rule** | First value MUST be less than second value |
| **Example** | `1732408000:1735000000` |

---

**Status:** ✅ ALL FILES CORRECTED  
**Date:** 2025  
**Issue:** RESOLVED