<?php
/**
 * User Profile Page
 *
 * Allows users to view their profile information and change their password.
 * Users must enter their current password to change to a new password.
 */

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'auth.php';

$message = '';
$messageType = 'info';

// Handle password change form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $message = 'Please fill in all password fields.';
        $messageType = 'danger';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'New passwords do not match.';
        $messageType = 'danger';
    } else {
        $result = changeUserPassword($pdo, $_SESSION['user_id'], $currentPassword, $newPassword);
        if ($result['success']) {
            $message = 'Password changed successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error: ' . $result['error'];
            $messageType = 'danger';
        }
    }
}

// Get current user information
$stmt = $pdo->prepare('SELECT id, username, role FROM users WHERE id = :id');
$stmt->execute([':id' => $_SESSION['user_id']]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

$pageTitle = 'Profile - AMT';
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h1><i class="fas fa-user-circle"></i> My Profile</h1>
        <p class="text-muted">View your profile information and change your password</p>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> Profile Information</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Username:</label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($currentUser['username']); ?></p>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Role:</label>
                    <p class="form-control-plaintext">
                        <span class="badge bg-<?php echo $currentUser['role'] === 'admin' ? 'danger' : 'secondary'; ?>">
                            <?php echo htmlspecialchars(ucfirst($currentUser['role'])); ?>
                        </span>
                    </p>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">User ID:</label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($currentUser['id']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-key"></i> Change Password</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> <strong>Password Requirements:</strong>
                    <ul class="mb-0 mt-2">
                        <li>At least 7 characters long</li>
                        <li>Contains uppercase letters (A-Z)</li>
                        <li>Contains lowercase letters (a-z)</li>
                        <li>Contains numbers (0-9)</li>
                        <li>Contains symbols (!@#$%^&* etc.)</li>
                    </ul>
                </div>

                <form method="POST" action="profile.php">
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Change Password
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>