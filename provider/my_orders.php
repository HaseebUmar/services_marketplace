<?php
session_start();
include("../config/db.php");

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider'){
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


// ✅ STATUS UPDATE LOGIC (FIXED POSITION)
if(isset($_GET['update']) && isset($_GET['status'])){

    $order_id = intval($_GET['update']);
    $status = $_GET['status'];

    // ✅ VALIDATION (IMPORTANT)
    $allowed = ['pending','accepted','handed_over','completed','cancelled'];
    if(!in_array($status, $allowed)){
        die("Invalid status");
    }

    $stmt = $conn->prepare("
        UPDATE orders o
        JOIN services s ON o.service_id = s.id
        SET o.status = ?
        WHERE o.id = ? AND s.user_id = ?
    ");

    $stmt->bind_param("sii", $status, $order_id, $user_id);
    $stmt->execute();

    header("Location: my_orders.php");
    exit();
}


// GET ORDERS
$query = "SELECT 
    o.id as order_id,
    o.service_id,
    o.customer_id,
    o.quantity,
    o.total_price,
    o.status,
    o.created_at,
    s.title as service_title,
    u.username as customer_name,
    u.email as customer_email
    FROM orders o
    JOIN services s ON o.service_id = s.id
    JOIN users u ON o.customer_id = u.id
    WHERE s.user_id = ?
    ORDER BY o.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result();
$total_orders = $orders->num_rows;


// STATUS COUNT
$status_query = "SELECT status, COUNT(*) as count FROM orders o
    JOIN services s ON o.service_id = s.id
    WHERE s.user_id = ?
    GROUP BY status";

$status_stmt = $conn->prepare($status_query);
$status_stmt->bind_param("i", $user_id);
$status_stmt->execute();
$status_result = $status_stmt->get_result();

$status_counts = [];
while($row = $status_result->fetch_assoc()){
    $status_counts[$row['status']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | ServiceHub</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/provider.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
.actions {
    display: flex;
    gap: 6px;
}

.btn {
    padding: 5px 10px;
    font-size: 11px;
    border-radius: 6px;
    text-decoration: none;
    color: #fff;
    font-weight: 600;
}

.accept { background: #3b82f6; }
.cancel { background: #ef4444; }
.hand { background: #8b5cf6; }
.complete { background: #22c55e; }

.status-badge {
    padding: 4px 8px;
    border-radius: 5px;
    font-size: 12px;
}

.status-pending { background: #fff3cd; }
.status-accepted { background: #cce5ff; }
.status-handed_over { background: #e2d9f3; }
.status-completed { background: #d4edda; }
.status-cancelled { background: #f8d7da; }

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
}
</style>

</head>
<body class="dashboard-body">

    <aside class="sidebar">
        <div class="sidebar__logo">
            <i class='bx bxs-zap'></i> <span>ServiceHub</span>
        </div>
        <nav class="sidebar__nav">
            <a href="dashboard.php"><i class='bx bxs-dashboard'></i> Dashboard</a>
            <a href="my_orders.php" class="active"><i class='bx bxs-briefcase'></i> My Orders</a>
            <a href="add_service.php"><i class='bx bxs-plus-circle'></i> Add Service</a>
            <a href="messages.php"><i class='bx bxs-envelope'></i> Messages</a>
            <a href="earnings.php"><i class='bx bxs-wallet'></i> Earnings</a>
            <div class="sidebar__divider"></div>
            <a href="../auth/logout.php" class="logout"><i class='bx bx-log-out'></i> Logout</a>
        </nav>
    </aside>

    <main class="dashboard-main">
        <header class="top-bar">
            <div class="top-bar__search">
                <i class='bx bx-search'></i>
                <input type="text" placeholder="Search orders..." id="search-orders">
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
                <div class="stat-card__icon blue"><i class='bx bx-box'></i></div>
                <div class="stat-card__data">
                    <p>Total Orders</p>
                    <h3><?php echo $total_orders; ?></h3>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card__icon green"><i class='bx bx-check-circle'></i></div>
                <div class="stat-card__data">
                    <p>Completed</p>
                    <h3><?php echo $status_counts['completed'] ?? 0; ?></h3>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card__icon purple"><i class='bx bx-time-five'></i></div>
                <div class="stat-card__data">
                    <p>Pending</p>
                    <h3><?php echo $status_counts['pending'] ?? 0; ?></h3>
                </div>
            </div>
        </section>

        <section class="listing-section">
            <div class="listing-header">
                <h2>All Orders</h2>
                <div class="filter-buttons">
                    <button class="filter-btn active" data-filter="all">All</button>
                    <button class="filter-btn" data-filter="pending">Pending</button>
                    <button class="filter-btn" data-filter="completed">Completed</button>
                </div>
            </div>

            <div class="orders-table">
                <?php if($total_orders > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Service</th>
                                <th>Customer</th>
                                <th>Quantity</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                 <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $orders->fetch_assoc()): ?>
                                <tr class="order-row" data-status="<?php echo $row['status']; ?>">
                                    <td><strong>#<?php echo $row['order_id']; ?></strong></td>
                                    <td><?php echo $row['service_title']; ?></td>
                                    <td>
                                        <div class="customer-info">
                                            <p><strong><?php echo $row['customer_name']; ?></strong></p>
                                            <small><?php echo $row['customer_email']; ?></small>
                                        </div>
                                    </td>
                                    <td><?php echo $row['quantity']; ?></td>
                                    <td><strong>$<?php echo number_format($row['total_price'], 2); ?></strong></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $row['status']; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                <td>
<div class="actions">

<?php if($row['status'] == 'pending'): ?>
<a href="?update=<?php echo $row['order_id']; ?>&status=accepted" class="btn accept">Accept</a>
<a href="?update=<?php echo $row['order_id']; ?>&status=cancelled" class="btn cancel">Cancel</a>
<?php endif; ?>

<?php if($row['status'] == 'accepted'): ?>
<a href="?update=<?php echo $row['order_id']; ?>&status=handed_over" class="btn hand">Handed Over</a>
<?php endif; ?>

<?php if($row['status'] == 'handed_over'): ?>
<a href="?update=<?php echo $row['order_id']; ?>&status=completed" class="btn complete">Complete</a>
<?php endif; ?>

</div>
</td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class='bx bx-inbox'></i>
                        <p>No orders yet. Promote your services to get started!</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script>
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const filter = this.dataset.filter;
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                document.querySelectorAll('.order-row').forEach(row => {
                    if(filter === 'all' || row.dataset.status === filter) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>