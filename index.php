<?php
include("config/db.php");

// Fetch all services with provider names
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
                            <img src="uploads/<?php echo $row['image']; ?>" class="service-card__img" alt="Service">
                            <span class="badge badge--<?php echo $row['service_type']; ?>">
                                <?php echo ucfirst($row['service_type']); ?>
                            </span>
                        </div>
                        
                        <div class="service-card__content">
                            <h3 class="service-card__title"><?php echo $row['title']; ?></h3>
                            <p class="service-card__provider">By: <strong><?php echo $row['provider_name']; ?></strong></p>
                            <p class="service-card__price">$<?php echo number_format($row['price'], 2); ?></p>
                            
                            <?php if($row['service_type'] == 'local'): ?>
                                <p class="service-card__location">📍 <?php echo $row['city']; ?></p>
                            <?php endif; ?>

                            <div class="service-card__footer">
                                <a href="auth/login.php" class="btn btn--primary btn--full">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No services found.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>