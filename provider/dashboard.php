<?php
session_start();
include("../config/db.php");

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider'){
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get services with average rating
$query = "SELECT s.*, 
          IFNULL(AVG(f.rating), 0) as avg_rating, 
          COUNT(f.id) as total_reviews
          FROM services s 
          LEFT JOIN feedback f ON s.id = f.service_id 
          WHERE s.user_id = ? 
          GROUP BY s.id ORDER BY s.id DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$services = $stmt->get_result();
$total_services = $services->num_rows;

$overall_rating = 0;
$total_reviews = 0;
while($row = $services->fetch_assoc()){
    $overall_rating += $row['avg_rating'] * $row['total_reviews'];
    $total_reviews += $row['total_reviews'];
}
$overall_rating = $total_reviews > 0 ? number_format($overall_rating / $total_reviews, 1) : 0;
$services->data_seek(0);

$pending_query = "SELECT SUM(o.total_price) as pending_earnings FROM orders o
    JOIN services s ON o.service_id = s.id
    WHERE s.user_id = ? AND o.status = 'pending'";
$pending_stmt = $conn->prepare($pending_query);
$pending_stmt->bind_param("i", $user_id);
$pending_stmt->execute();
$pending_result = $pending_stmt->get_result()->fetch_assoc();
$pending_earnings = $pending_result['pending_earnings'] ?? 0;

$dashboard_notice = '';
if(isset($_GET['notice']) && $_GET['notice'] === 'one_service_only'){
    $dashboard_notice = '<div class="alert alert--error">You can only add one service. Update or remove your existing service if you need to make a change.</div>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pro Dashboard | ServiceHub</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/provider.css">
    <!-- Boxicons for Professional Icons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="dashboard-body">

    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="sidebar__logo">
            <i class='bx bxs-zap'></i> <span>ServiceHub</span>
        </div>
        <nav class="sidebar__nav">
            <a href="dashboard.php" class="active"><i class='bx bxs-dashboard'></i> Dashboard</a>
            <a href="my_orders.php"><i class='bx bxs-briefcase'></i> My Orders</a>
            <?php if($total_services == 0): ?>
                <a href="add_service.php"><i class='bx bxs-plus-circle'></i> Add Service</a>
            <?php else: ?>
                <span class="sidebar__disabled"><i class='bx bxs-plus-circle'></i> Add Service</span>
            <?php endif; ?>
            <a href="messages.php"><i class='bx bxs-envelope'></i> Messages</a>
            <a href="earnings.php"><i class='bx bxs-wallet'></i> Earnings</a>
            <div class="sidebar__divider"></div>
            <a href="../auth/logout.php" class="logout"><i class='bx bx-log-out'></i> Logout</a>
        </nav>
    </aside>

    <main class="dashboard-main">
        <!-- Top Header -->
        <header class="top-bar">
            <div class="top-bar__search">
                <i class='bx bx-search'></i>
                <input type="text" placeholder="Search your services...">
            </div>
            <div class="top-bar__profile">
                <div class="profile-info">
                    <p>Welcome back,</p>
                    <strong><?php echo $_SESSION['username']; ?></strong>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?php echo $_SESSION['username']; ?>&background=random" alt="Avatar">
            </div>
        </header>

        <?php if(!empty($dashboard_notice)): ?>
            <?php echo $dashboard_notice; ?>
        <?php endif; ?>

        <!-- Stats Section -->
        <section class="stats-container">
            <div class="stat-card">
                <div class="stat-card__icon blue"><i class='bx bx-layer'></i></div>
                <div class="stat-card__data">
                    <p>Active Services</p>
                    <h3><?php echo $total_services; ?></h3>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card__icon green"><i class='bx bx-star'></i></div>
                <div class="stat-card__data">
                    <p>Avg Rating</p>
                    <h3><?php echo $overall_rating; ?> <small>/ 5</small></h3>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card__icon purple"><i class='bx bx-dollar-circle'></i></div>
                <div class="stat-card__data">
                    <p>Pending Earnings</p>
                    <h3>$<?php echo number_format($pending_earnings, 2); ?></h3>
                </div>
            </div>
        </section>

        <!-- Services Listing -->
        <section class="listing-section">
            <div class="listing-header">
                <h2>Manage Services</h2>
                <?php if($total_services == 0): ?>
                    <a href="add_service.php" class="btn-new-service">+ Create New</a>
                <?php else: ?>
                    <span class="btn-new-service btn-new-service--disabled">Only one service allowed</span>
                <?php endif; ?>
            </div>

            <div class="pro-grid">
                <?php if($total_services > 0): ?>
                    <?php while($row = $services->fetch_assoc()): ?>
                        <div class="pro-card">
                            <div class="pro-card__img">
                                <?php 
                                    $imagePath = "../uploads/" . $row['image'];
                                    $img = (!empty($row['image']) && file_exists($imagePath)) ? $imagePath : "../css/placeholder.jpg";
                                ?>
                                <img src="<?php echo $img; ?>" alt="Service">
                                <span class="pro-badge"><?php echo strtoupper($row['service_type']); ?></span>
                            </div>
                            <div class="pro-card__info">
                                <h4><?php echo $row['title']; ?></h4>
                                <div class="pro-card__meta">
                                    <span class="price">$<?php echo number_format($row['price'], 2); ?></span>
                                    <span class="reviews"><i class='bx bxs-star'></i> <?php echo number_format($row['avg_rating'], 1); ?> (<?php echo $row['total_reviews']; ?>)</span>
                                </div>
                                <div class="pro-card__actions">
                                    <a href="edit_service.php?id=<?php echo $row['id']; ?>" class="edit-link"><i class='bx bx-edit-alt'></i> Edit</a>
                                    <a href="delete_service.php?id=<?php echo $row['id']; ?>" class="delete-link" onclick="return confirm('Delete permanently?')"><i class='bx bx-trash'></i></a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <img src="../assets/empty.svg" alt="Empty">
                        <p>No services found. Start your journey today!</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

</body>
</html>