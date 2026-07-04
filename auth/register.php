<?php
include("../config/db.php");
$message = "";

if(isset($_POST['register'])){
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $provider_type = null;
    $provider_category = null;
    $contact_phone = null;

    if($role === 'provider'){
        $provider_type = $_POST['provider_type'] ?? 'online';
        $provider_category = $_POST['provider_category'] ?? null;
        $contact_phone = mysqli_real_escape_string($conn, $_POST['contact_phone'] ?? '');
    }

    $check = $conn->prepare("SELECT id FROM users WHERE email=?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if($check->num_rows > 0){
        $message = "<div class='alert alert--error'>Email already registered!</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, provider_type, provider_category, contact_phone) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $username, $email, $password, $role, $provider_type, $provider_category, $contact_phone);
        if($stmt->execute()){
            $message = "<div class='alert alert--success'>Registration successful! <a href='login.php'>Login here</a></div>";
        } else {
            $message = "<div class='alert alert--error'>Registration failed. Try again.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Services Marketplace</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/auth.css">
</head>
<body class="auth-layout">
    <div class="auth-card">
        <h2 class="auth-card__title">Join as Provider or Customer</h2>
        <?php echo $message; ?>
        <form method="POST" class="auth-card__form">
            <input type="text" name="username" class="auth-card__input" placeholder="Full Name" required>
            <input type="email" name="email" class="auth-card__input" placeholder="Email Address" required>
            <input type="password" name="password" class="auth-card__input" placeholder="Create Password" required>
            <select name="role" id="role_select" class="auth-card__select" onchange="toggleProviderFields(this.value)">
                <option value="customer">I am a Customer</option>
                <option value="provider">I am a Service Provider</option>
            </select>

            <div id="provider_details" style="display:none;">
                <select name="provider_type" id="provider_type" class="auth-card__select" onchange="populateCategories(this.value)">
                    <option value="online">Only Online Services</option>
                    <option value="local">Local Services</option>
                </select>

                <select name="provider_category" id="provider_category" class="auth-card__select">
                    <option value="">Select Your Main Category</option>
                </select>

                <input type="tel" name="contact_phone" class="auth-card__input" placeholder="Contact Phone (optional)">
            </div>

            <button name="register" class="auth-card__button">Create Account</button>
            <p class="auth-card__footer">Already have an account? <a href="login.php">Login</a></p>
        </form>
    </div>

    <script>
        const providerCategories = {
            online: [
                'Web & Tech',
                'Tutoring & Coaching',
                'Consulting Services',
                'Digital Marketing'
            ],
            local: [
                'Home Repair',
                'Cleaning & Maintenance',
                'Beauty & Wellness',
                'Delivery & Moving'
            ]
        };

        function toggleProviderFields(role) {
            const details = document.getElementById('provider_details');
            const categorySelect = document.getElementById('provider_category');
            details.style.display = (role === 'provider') ? 'block' : 'none';
            categorySelect.required = (role === 'provider');
            if(role === 'provider') {
                populateCategories(document.getElementById('provider_type').value);
            }
        }

        function populateCategories(type) {
            const categorySelect = document.getElementById('provider_category');
            categorySelect.innerHTML = '<option value="">Select Your Main Category</option>';
            providerCategories[type].forEach(category => {
                const option = document.createElement('option');
                option.value = category;
                option.textContent = category;
                categorySelect.appendChild(option);
            });
        }
    </script>
</body>
</html>