<?php
session_start();
include("../config/db.php");
$message = "";

if(isset($_POST['send_reset'])){
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $user = $result->fetch_assoc();
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $insertStmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)");
        $insertStmt->bind_param("iss", $user['id'], $token, $expires_at);
        $insertStmt->execute();

        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/reset_password.php?token=" . $token;
        $subject = "Password Reset Request";
        $body = "Hello,\n\nWe received a request to reset your password. Click the link below to reset it:\n\n" . $reset_link . "\n\nIf you did not request this, please ignore this message.\n";
        $headers = "From: no-reply@" . $_SERVER['HTTP_HOST'];

        $mailSent = mail($email, $subject, $body, $headers);
        $message = "<div class='alert alert--success'>If this email exists, a password reset link has been sent.</div>";

        if(!$mailSent){
            $message .= "<div class='alert alert--info'>Server mail is not configured. Use this reset link for testing:<br><a href='" . htmlspecialchars($reset_link) . "'>" . htmlspecialchars($reset_link) . "</a></div>";
        }
    } else {
        $message = "<div class='alert alert--success'>If this email exists, a password reset link has been sent.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/auth.css">
</head>
<body class="auth-layout">
    <div class="auth-card auth-card--large">
        <h2 class="auth-card__title">Reset Your Password</h2>
        <?php echo $message; ?>
        <form method="POST" class="auth-card__form">
            <input type="email" name="email" class="auth-card__input" placeholder="Enter your email address" required>
            <button name="send_reset" class="auth-card__button">Send Reset Link</button>
            <p class="auth-card__help"><a href="login.php">Back to login</a></p>
        </form>
    </div>
</body>
</html>