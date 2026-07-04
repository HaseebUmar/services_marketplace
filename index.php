<?php
include("config/db.php");

$sql = "SELECT s.*, u.username as provider_name FROM services s 
        JOIN users u ON s.user_id = u.id ORDER BY s.id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Marketplace | Home</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/customer.css">
</head>
<body>

<nav class="navbar">
    <div class="navbar__logo">ServiceHub</div>
    <div class="navbar__links">
        <a href="auth/login.php" class="btn btn--primary">Login</a>
        <a href="auth/register.php" class="btn btn--outline">Register</a>
    </div>
</nav>

<header class="hero-section">
    <h1>Find Professional Services Near You</h1>
    <p>Browse through local and online services offered by experts.</p>
</header>

<main class="container">
    <h2 class="section-title">Available Services</h2>

    <div class="services-grid">

        <?php if($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>

                <div class="service-card">
                    <div class="service-card__image-wrapper">
                        <img src="uploads/<?php echo $row['image']; ?>" class="service-card__img">
                        <span class="badge badge--<?php echo $row['service_type']; ?>">
                            <?php echo ucfirst($row['service_type']); ?>
                        </span>
                    </div>

                    <div class="service-card__content">
                        <h3><?php echo $row['title']; ?></h3>
                        <p>By: <?php echo $row['provider_name']; ?></p>
                        <p>$<?php echo $row['price']; ?></p>
                    </div>
                </div>

            <?php endwhile; ?>
        <?php else: ?>
            <p>No services found</p>
        <?php endif; ?>

    </div>
</main>

</body>
</html>