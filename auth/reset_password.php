<?php
session_start();
include("../config/db.php");

$message = "";
$token = $_GET['token'] ?? '';
$validToken = false;
$user_id = null;

if($token){
    $stmt = $conn->prepare("SELECT pr.user_id, u.email FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.token = ? AND pr.expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if($row = $result->fetch_assoc()){
        $validToken = true;
        $user_id = $row['user_id'];
    }
}

if(isset($_POST['reset_password']) && $token){
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if($password !== $confirm_password){
        $message = "<div class='alert alert--error'>Passwords do not match.</div>";
    } elseif(strlen($password) < 6) {
        $message = "<div class='alert alert--error'>Password must be at least 6 characters.</div>";
    } elseif($validToken && $user_id) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $updateStmt->bind_param("si", $passwordHash, $user_id);
        $updateStmt->execute();

        $deleteStmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
        $deleteStmt->bind_param("i", $user_id);
        $deleteStmt->execute();

        $message = "<div class='alert alert--success'>Your password has been reset successfully. <a href='login.php'>Login here</a>.</div>";
        $validToken = false;
    } else {
        $message = "<div class='alert alert--error'>Invalid or expired reset link. Please request a new one.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/auth.css">
</head>
<body class="auth-layout">
    <div class="auth-card auth-card--large">
        <h2 class="auth-card__title">Reset Password</h2>
        <?php echo $message; ?>

        <?php if($validToken): ?>
            <form method="POST" class="auth-card__form">
                <input type="password" name="password" class="auth-card__input" placeholder="New password" required>
                <input type="password" name="confirm_password" class="auth-card__input" placeholder="Confirm password" required>
                <button type="submit" name="reset_password" class="auth-card__button">Save New Password</button>
            </form>
        <?php else: ?>
            <p class="auth-card__help">This reset link is invalid or expired. Please use the forgot password form again.</p>
            <a href="forgot_password.php" class="btn btn--primary">Request New Link</a>
        <?php endif; ?>
    </div>
</body>
</html>