<?php
session_start();
include("../config/db.php");

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider'){
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Total earnings
$total_query = "SELECT SUM(o.total_price) as total_earnings FROM orders o
    JOIN services s ON o.service_id = s.id
    WHERE s.user_id = ? AND o.status = 'completed'";
$total_stmt = $conn->prepare($total_query);
$total_stmt->bind_param("i", $user_id);
$total_stmt->execute();
$total_result = $total_stmt->get_result()->fetch_assoc();
$total_earnings = $total_result['total_earnings'] ?? 0;

// Earnings this month
$month_query = "SELECT SUM(o.total_price) as month_earnings FROM orders o
    JOIN services s ON o.service_id = s.id
    WHERE s.user_id = ? AND o.status = 'completed' 
    AND MONTH(o.created_at) = MONTH(CURDATE()) 
    AND YEAR(o.created_at) = YEAR(CURDATE())";
$month_stmt = $conn->prepare($month_query);
$month_stmt->bind_param("i", $user_id);
$month_stmt->execute();
$month_result = $month_stmt->get_result()->fetch_assoc();
$month_earnings = $month_result['month_earnings'] ?? 0;

// Pending earnings
$pending_query = "SELECT SUM(o.total_price) as pending_earnings FROM orders o
    JOIN services s ON o.service_id = s.id
    WHERE s.user_id = ? AND o.status = 'pending'";
$pending_stmt = $conn->prepare($pending_query);
$pending_stmt->bind_param("i", $user_id);
$pending_stmt->execute();
$pending_result = $pending_stmt->get_result()->fetch_assoc();
$pending_earnings = $pending_result['pending_earnings'] ?? 0;

// Earnings by service
$service_query = "SELECT 
    s.id,
    s.title,
    COUNT(o.id) as order_count,
    SUM(o.total_price) as service_earnings
    FROM services s
    LEFT JOIN orders o ON s.id = o.service_id AND o.status = 'completed'
    WHERE s.user_id = ?
    GROUP BY s.id
    ORDER BY service_earnings DESC";
$service_stmt = $conn->prepare($service_query);
$service_stmt->bind_param("i", $user_id);
$service_stmt->execute();
$services = $service_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings | ServiceHub</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/provider.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="dashboard-body">

    <aside class="sidebar">
        <div class="sidebar__logo">
            <i class='bx bxs-zap'></i> <span>ServiceHub</span>
        </div>
        <nav class="sidebar__nav">
            <a href="dashboard.php"><i class='bx bxs-dashboard'></i> Dashboard</a>
            <a href="my_orders.php"><i class='bx bxs-briefcase'></i> My Orders</a>
            <a href="add_service.php"><i class='bx bxs-plus-circle'></i> Add Service</a>
            <a href="messages.php"><i class='bx bxs-envelope'></i> Messages</a>
            <a href="earnings.php" class="active"><i class='bx bxs-wallet'></i> Earnings</a>
            <div class="sidebar__divider"></div>
            <a href="../auth/logout.php" class="logout"><i class='bx bx-log-out'></i> Logout</a>
        </nav>
    </aside>

    <main class="dashboard-main">
        <header class="top-bar">
            <div class="top-bar__search">
                <i class='bx bx-search'></i>
                <input type="text" placeholder="Search earnings..." id="search-earnings">
            </div>
            <div class="top-bar__profile">
                <div class="profile-info">
                    <p>Welcome back,</p>
                    <strong><?php echo $_SESSION['username']; ?></strong>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?php echo $_SESSION['username']; ?>&background=random" alt="Avatar">
            </div>
        </header>

        <section class="stats-container">
            <div class="stat-card">
                <div class="stat-card__icon green"><i class='bx bx-dollar-circle'></i></div>
                <div class="stat-card__data">
                    <p>Total Earnings</p>
                    <h3>$<?php echo number_format($total_earnings, 2); ?></h3>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card__icon blue"><i class='bx bx-calendar'></i></div>
                <div class="stat-card__data">
                    <p>This Month</p>
                    <h3>$<?php echo number_format($month_earnings, 2); ?></h3>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card__icon purple"><i class='bx bx-time'></i></div>
                <div class="stat-card__data">
                    <p>Pending</p>
                    <h3>$<?php echo number_format($pending_earnings, 2); ?></h3>
                </div>
            </div>
        </section>

        <section class="listing-section">
            <div class="listing-header">
                <h2>Earnings by Service</h2>
            </div>

            <div class="earnings-table">
                <?php if($services->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Service Name</th>
                                <th>Orders Completed</th>
                                <th>Total Earnings</th>
                                <th>Average per Order</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($service = $services->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo $service['title']; ?></strong></td>
                                    <td><span class="badge-count"><?php echo $service['order_count'] ?? 0; ?></span></td>
                                    <td><strong style="color: #16a34a;">$<?php echo number_format($service['service_earnings'] ?? 0, 2); ?></strong></td>
                                    <td>$<?php echo number_format(($service['service_earnings'] ?? 0) / max(1, $service['order_count'] ?? 1), 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class='bx bx-wallet'></i>
                        <p>No earnings yet. Create and promote your services!</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="listing-section" style="margin-top: 2rem;">
            <div class="listing-header">
                <h2>Payment Information</h2>
            </div>
            <div class="payment-info">
                <div class="info-row">
                    <span>Account Status:</span>
                    <strong style="color: #16a34a;">Active</strong>
                </div>
                <div class="info-row">
                    <span>Last Payout:</span>
                    <strong>Pending Review</strong>
                </div>
                <div class="info-row">
                    <span>Minimum Payout Amount:</span>
                    <strong>$50.00</strong>
                </div>
                <button class="btn btn--primary" style="width: auto; margin-top: 1rem;">Request Payout</button>
            </div>
        </section>
    </main>
</body>
</html>