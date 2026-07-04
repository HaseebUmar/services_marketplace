<?php
session_start();
include("../config/db.php");
$message = "";

if(isset($_POST['login'])){
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($user = $result->fetch_assoc()){
        if(password_verify($password, $user['password'])){
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if($user['role'] == 'provider'){
                header("Location: ../provider/dashboard.php");
            } else {
                header("Location: ../customer/browse.php");
            }
            exit();
        } else {
            $message = "<div class='alert alert--error'>Invalid Password!</div>";
        }
    } else {
        $message = "<div class='alert alert--error'>No user found with this email!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Services Marketplace</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/auth.css">
</head>
<body class="auth-layout">
    <div class="auth-card">
        <h2 class="auth-card__title">Welcome Back</h2>
        <?php echo $message; ?>
        <form method="POST" class="auth-card__form">
            <input type="email" name="email" class="auth-card__input" placeholder="Email Address" required>
            <input type="password" name="password" class="auth-card__input" placeholder="Password" required>
            <button name="login" class="auth-card__button">Login</button>
            <p class="auth-card__help"><a href="forgot_password.php">Forgot password?</a></p>
            <p class="auth-card__footer">Don't have an account? <a href="register.php">Register</a></p>
        </form>
    </div>
</body>
</html>