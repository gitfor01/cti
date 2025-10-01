# Password Change Feature Documentation

## Overview
This document describes the password change functionality added to the AMT (Assurance Monitoring Tool) system.

## Features Added

### 1. Password Validation
All passwords must meet the following requirements:
- **Minimum length**: 7 characters
- **Uppercase letters**: At least one (A-Z)
- **Lowercase letters**: At least one (a-z)
- **Numbers**: At least one (0-9)
- **Symbols**: At least one special character (!@#$%^&* etc.)

### 2. User Self-Service Password Change
- **Location**: `profile.php` (accessible via username link in navigation bar)
- **Requirements**: User must enter their current password to verify identity
- **Process**:
  1. User clicks on their username in the navigation bar
  2. Navigates to Profile page
  3. Enters current password
  4. Enters new password (must meet requirements)
  5. Confirms new password
  6. Submits form

### 3. Admin Password Change for Other Users
- **Location**: `admin.php` (Admin Panel)
- **Requirements**: Admin role required
- **Privacy**: Admin does NOT see the user's current password
- **Process**:
  1. Admin navigates to Admin Panel
  2. Clicks the key icon (🔑) next to any user
  3. Modal opens with password change form
  4. Admin enters new password (must meet requirements)
  5. Confirms new password
  6. Submits form

## Files Modified

### 1. `includes/functions.php`
Added three new functions:

#### `validatePassword($password)`
Validates password strength according to requirements.
- **Parameters**: `$password` (string) - Password to validate
- **Returns**: Array with `valid` (bool) and `error` (string) keys

#### `changeUserPassword($pdo, $userId, $currentPassword, $newPassword)`
Allows a user to change their own password.
- **Parameters**:
  - `$pdo` - Database connection
  - `$userId` - User ID
  - `$currentPassword` - Current password for verification
  - `$newPassword` - New password
- **Returns**: Array with `success` (bool) and `error` (string) keys

#### `adminChangeUserPassword($pdo, $targetUserId, $newPassword)`
Allows an admin to change another user's password without knowing the current password.
- **Parameters**:
  - `$pdo` - Database connection
  - `$targetUserId` - ID of user whose password will be changed
  - `$newPassword` - New password
- **Returns**: Array with `success` (bool) and `error` (string) keys

#### Modified: `addUser($pdo, $username, $password, $role)`
Updated to validate password strength when creating new users.
- **Return type changed**: Now returns array instead of boolean

### 2. `admin.php`
- Updated to handle new return format from `addUser()` function
- Added password change form handler
- Added password change modal for each user
- Added key icon button in user table for password changes
- Added password requirements hint in user creation form

### 3. `includes/header.php`
- Changed username display to be a clickable link to profile page

### 4. `profile.php` (NEW FILE)
New page for users to:
- View their profile information (username, role, user ID)
- Change their own password
- See password requirements

## Testing

### Test Script
A test script is provided: `test_password_validation.php`

Run this script to verify password validation works correctly with various test cases.

### Manual Testing

#### Test User Password Change:
1. Log in as any user
2. Click on your username in the navigation bar
3. Try changing password with:
   - Weak password (should fail)
   - Password without symbols (should fail)
   - Valid strong password (should succeed)

#### Test Admin Password Change:
1. Log in as admin
2. Go to Admin Panel
3. Click key icon next to any user
4. Try changing password with:
   - Weak password (should fail)
   - Valid strong password (should succeed)

#### Test New User Creation:
1. Log in as admin
2. Go to Admin Panel
3. Try creating user with weak password (should fail with error message)
4. Create user with strong password (should succeed)

## Security Features

1. **Password Hashing**: All passwords are hashed using PHP's `password_hash()` with `PASSWORD_DEFAULT`
2. **Current Password Verification**: Users must know their current password to change it
3. **Admin Privacy**: Admins cannot see users' current passwords
4. **Password Strength**: Enforced minimum requirements prevent weak passwords
5. **Prepared Statements**: All database queries use prepared statements to prevent SQL injection

## Password Examples

### Valid Passwords:
- `Valid1!`
- `MyP@ssw0rd`
- `Str0ng!Pass`
- `Test123!`
- `Admin#2024`

### Invalid Passwords:
- `weak` - Too short
- `NoNumbers!` - Missing numbers
- `nonumbers1!` - Missing uppercase
- `NOLOWERCASE1!` - Missing lowercase
- `NoSymbols123` - Missing symbols

## User Interface

### Profile Page Features:
- Clean card-based layout
- Profile information display
- Password change form with requirements
- Success/error messages
- Back to dashboard button

### Admin Panel Features:
- Key icon button for each user
- Bootstrap modal for password change
- Password requirements displayed in modal
- Success/error messages
- No display of current passwords

## Future Enhancements (Optional)

Potential improvements that could be added:
1. Password strength meter (visual indicator)
2. Password history (prevent reusing recent passwords)
3. Password expiration policy
4. Two-factor authentication
5. Password reset via email
6. Account lockout after failed attempts
7. Password complexity score display

## Support

For issues or questions:
1. Check error messages displayed in the UI
2. Review password requirements
3. Test with `test_password_validation.php`
4. Check server logs for detailed errors