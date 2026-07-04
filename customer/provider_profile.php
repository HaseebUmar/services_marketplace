<?php
session_start();
include("../config/db.php");

if(!isset($_GET['id'])){ header("Location: browse.php"); exit(); }

$provider_id = $_GET['id'];

// Get provider basic info
$provider_stmt = $conn->prepare("SELECT id, username, email, contact_phone, provider_type, provider_category FROM users WHERE id = ? AND role = 'provider'");
$provider_stmt->bind_param("i", $provider_id);
$provider_stmt->execute();
$provider = $provider_stmt->get_result()->fetch_assoc();

if(!$provider){ header("Location: browse.php"); exit(); }

// Get all services by this provider
$services_query = "SELECT s.*, 
                   IFNULL(AVG(f.rating), 0) as avg_rating, 
                   COUNT(f.id) as total_reviews
                   FROM services s 
                   LEFT JOIN feedback f ON s.id = f.service_id 
                   WHERE s.user_id = ?
                   GROUP BY s.id 
                   ORDER BY s.id DESC";
$services_stmt = $conn->prepare($services_query);
$services_stmt->bind_param("i", $provider_id);
$services_stmt->execute();
$services = $services_stmt->get_result();

// Calculate overall stats
$total_services = $services->num_rows;
$overall_rating = 0;
$total_reviews = 0;

$services->data_seek(0); // Reset pointer
while($service = $services->fetch_assoc()){
    $overall_rating += $service['avg_rating'] * $service['total_reviews'];
    $total_reviews += $service['total_reviews'];
}

$overall_rating = $total_reviews > 0 ? number_format($overall_rating / $total_reviews, 1) : 0;

// Handle contact form submission
$message = "";
if(isset($_POST['send_message']) && isset($_SESSION['user_id']) && $_SESSION['role'] == 'customer'){
    $customer_id = $_SESSION['user_id'];
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $message_text = mysqli_real_escape_string($conn, $_POST['message']);

    // For now, we'll store messages in a simple messages table
    // In a real app, you'd want email notifications or a messaging system
    $msg_stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");
    $msg_stmt->bind_param("iiss", $customer_id, $provider_id, $subject, $message_text);

    if($msg_stmt->execute()){
        $message = "<div class='alert alert--success'>Message sent successfully! <a href='messages.php?provider_id=$provider_id'>Continue chat</a>.</div>";
    } else {
        $message = "<div class='alert alert--error'>Failed to send message. Please try again.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $provider['username']; ?> | ServiceHub</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/customer.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="navbar__logo">
            <i class='bx bxs-zap'></i> ServiceHub
        </div>
        <div class="navbar__links">
            <a href="browse.php">Browse Services</a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <?php if($_SESSION['role'] == 'customer'): ?>
                    <a href="../customer/browse.php">Dashboard</a>
                    <a href="messages.php">Messages</a>
                <?php else: ?>
                    <a href="../provider/dashboard.php">Dashboard</a>
                <?php endif; ?>
                <a href="../auth/logout.php">Logout</a>
            <?php else: ?>
                <a href="../auth/login.php">Login</a>
                <a href="../auth/register.php">Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <main class="container" style="max-width: 1200px;">
        <!-- Provider Header -->
        <section class="profile-header">
            <div class="profile-avatar">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($provider['username']); ?>&background=random&size=120" alt="Provider Avatar">
            </div>
            <div class="profile-info">
                <h1><?php echo $provider['username']; ?></h1>
                <div class="profile-stats">
                    <div class="stat">
                        <span class="stat-number"><?php echo $total_services; ?></span>
                        <span class="stat-label">Services</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number"><?php echo $overall_rating; ?> <i class='bx bxs-star' style="color: #fbbf24;"></i></span>
                        <span class="stat-label">Rating</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number"><?php echo $total_reviews; ?></span>
                        <span class="stat-label">Reviews</span>
                    </div>
                </div>
                <p class="profile-description">Professional service provider offering quality services to customers.</p>
            </div>
        </section>

        <div class="profile-grid">
            <!-- Services Section -->
            <section class="profile-services">
                <h2>Services Offered (<?php echo $total_services; ?>)</h2>

                <?php if($total_services > 0): ?>
                    <div class="services-grid">
                        <?php
                        $services->data_seek(0); // Reset pointer
                        while($service = $services->fetch_assoc()):
                        ?>
                            <div class="service-card">
                                <div class="service-card__img">
                                    <?php
                                    $imagePath = "../uploads/" . $service['image'];
                                    $img = (!empty($service['image']) && file_exists($imagePath)) ? $imagePath : "../css/placeholder.jpg";
                                    ?>
                                    <img src="<?php echo $img; ?>" alt="Service">
                                    <span class="service-badge"><?php echo strtoupper($service['service_type']); ?></span>
                                </div>
                                <div class="service-card__info">
                                    <h4><?php echo $service['title']; ?></h4>
                                    <p><?php echo substr($service['description'], 0, 100); ?>...</p>
                                    <div class="service-card__meta">
                                        <span class="price">$<?php echo number_format($service['price'], 2); ?></span>
                                        <span class="rating">
                                            <i class='bx bxs-star'></i> <?php echo number_format($service['avg_rating'], 1); ?>
                                            (<?php echo $service['total_reviews']; ?>)
                                        </span>
                                    </div>
                                    <a href="service_detail.php?id=<?php echo $service['id']; ?>" class="btn btn--primary">View Details</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class='bx bx-package'></i>
                        <p>No services available yet.</p>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Contact Section -->
            <aside class="profile-sidebar">
                <div class="contact-card">
                    <h3>Contact Provider</h3>
                    <?php echo $message; ?>

                    <?php if(isset($_SESSION['user_id']) && $_SESSION['role'] == 'customer'): ?>
                        <form method="POST" class="contact-form">
                            <div class="form-group">
                                <label for="subject">Subject</label>
                                <input type="text" id="subject" name="subject" required placeholder="What is this about?">
                            </div>
                            <div class="form-group">
                                <label for="message">Message</label>
                                <textarea id="message" name="message" required placeholder="Describe your requirements..." rows="4"></textarea>
                            </div>
                            <button type="submit" name="send_message" class="btn btn--primary">Send Message</button>
                        </form>
                    <?php else: ?>
                        <div class="contact-info">
                            <p><i class='bx bx-envelope'></i> <?php echo $provider['email']; ?></p>
                            <?php if(!empty($provider['contact_phone'])): ?>
                                <p><i class='bx bx-phone'></i> <?php echo htmlspecialchars($provider['contact_phone']); ?></p>
                            <?php endif; ?>
                            <?php if(!empty($provider['provider_type']) && !empty($provider['provider_category'])): ?>
                                <p><strong>Primary Offering:</strong> <?php echo ucfirst($provider['provider_type']) . ' - ' . htmlspecialchars($provider['provider_category']); ?></p>
                            <?php endif; ?>
                            <p class="login-required">Please <a href="../auth/login.php">login as customer</a> to contact this provider.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Provider Stats Card -->
                <div class="stats-card">
                    <h3>Provider Stats</h3>
                    <div class="stats-list">
                        <div class="stat-item">
                            <span>Total Services</span>
                            <strong><?php echo $total_services; ?></strong>
                        </div>
                        <div class="stat-item">
                            <span>Average Rating</span>
                            <strong><?php echo $overall_rating; ?>/5</strong>
                        </div>
                        <div class="stat-item">
                            <span>Total Reviews</span>
                            <strong><?php echo $total_reviews; ?></strong>
                        </div>
                        <div class="stat-item">
                            <span>Member Since</span>
                            <strong>2024</strong>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <style>
        .profile-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            padding: 2rem;
            background: var(--surface);
            border-radius: 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .profile-avatar img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid var(--primary-color);
        }

        .profile-info h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }

        .profile-stats {
            display: flex;
            gap: 2rem;
            margin: 1rem 0;
        }

        .stat {
            text-align: center;
        }

        .stat-number {
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .profile-description {
            color: var(--text-muted);
            font-size: 1rem;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 2rem;
        }

        .profile-services h2 {
            margin-bottom: 1.5rem;
            color: var(--text-dark);
        }

        .contact-card,
        .stats-card {
            background: var(--surface);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .contact-card h3,
        .stats-card h3 {
            margin-bottom: 1rem;
            color: var(--text-dark);
        }

        .contact-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: 500;
            color: var(--text-dark);
        }

        .form-group input,
        .form-group textarea {
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            font-family: inherit;
        }

        .contact-info p {
            margin: 0.5rem 0;
            color: var(--text-muted);
        }

        .login-required {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .stats-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border);
        }

        .stat-item:last-child {
            border-bottom: none;
        }

        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .profile-grid {
                grid-template-columns: 1fr;
            }

            .profile-stats {
                justify-content: center;
            }
        }
    </style>
</body>
</html>