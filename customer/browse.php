<?php
session_start();
include("../config/db.php");

// Security Check
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer'){
    header("Location: ../auth/login.php");
    exit();
}

// Filter Logic
$service_types = [
    'local' => 'Local Services',
    'online' => 'Online Services'
];
$service_categories = [
    'local' => ['Home Repair', 'Cleaning & Maintenance', 'Beauty & Wellness', 'Delivery & Moving'],
    'online' => ['Web & Tech', 'Tutoring & Coaching', 'Consulting Services', 'Digital Marketing']
];

$type_filter = isset($_GET['type']) && array_key_exists($_GET['type'], $service_types) ? $_GET['type'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$valid_categories = array_merge($service_categories['local'], $service_categories['online']);
if($category_filter && !in_array($category_filter, $valid_categories)) {
    $category_filter = '';
}

$sql = "SELECT s.*, u.username as provider_name FROM services s 
        JOIN users u ON s.user_id = u.id";
$where = [];
if($type_filter) {
    $where[] = "s.service_type = '" . $conn->real_escape_string($type_filter) . "'";
}
if($category_filter) {
    $where[] = "s.category = '" . $conn->real_escape_string($category_filter) . "'";
}
if(count($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browse Services</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/customer.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar__logo">ServiceHub</div>
        <div class="navbar__links">
            <a href="browse.php">All Services</a>
            <a href="messages.php">Messages</a>
            <a href="my_orders.php">My Orders</a>
            <a href="../auth/logout.php" class="btn btn--danger">Logout</a>
        </div>
    </nav>

    <main class="container">
        <div class="browse-layout">
            <aside class="service-sidebar">
                <div class="service-sidebar__section">
                    <h3 class="service-sidebar__heading">Service Types</h3>
                    <?php foreach($service_types as $type_key => $type_label): ?>
                        <a href="browse.php?type=<?php echo $type_key; ?>" class="service-sidebar__link <?php echo ($type_filter === $type_key && $category_filter === '') ? 'active' : ''; ?>">
                            <?php echo $type_label; ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <?php foreach($service_categories as $type_key => $categories): ?>
                    <div class="service-sidebar__section">
                        <h4 class="service-sidebar__subheading"><?php echo ucfirst($type_key); ?> Categories</h4>
                        <?php foreach($categories as $category): ?>
                            <a href="browse.php?type=<?php echo $type_key; ?>&category=<?php echo urlencode($category); ?>" class="service-sidebar__link <?php echo ($category_filter === $category) ? 'active' : ''; ?>">
                                <?php echo $category; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </aside>

            <section class="service-results">
                <div class="filter-bar">
                    <form method="GET" class="filter-bar__form">
                        <select name="type" onchange="this.form.submit()">
                            <option value="">All Types</option>
                            <option value="local" <?php if($type_filter == 'local') echo 'selected'; ?>>Local</option>
                            <option value="online" <?php if($type_filter == 'online') echo 'selected'; ?>>Online</option>
                        </select>
                    </form>
                </div>

                <?php if($category_filter || $type_filter): ?>
                    <div class="active-filter">
                        Showing: <?php echo $type_filter ? ucfirst($type_filter) . ' services' : 'All services'; ?><?php echo $category_filter ? ' › ' . htmlspecialchars($category_filter) : ''; ?>
                        <a href="browse.php" class="btn btn--outline">Reset</a>
                    </div>
                <?php endif; ?>

                <div class="services-grid">
                    <?php if($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <div class="service-card">
                                <img src="../uploads/<?php echo $row['image']; ?>" class="service-card__img">
                                <div class="service-card__content">
                                    <span class="badge badge--<?php echo $row['service_type']; ?>">
                                        <?php echo ucfirst($row['service_type']); ?>
                                    </span>
                                    <h3><?php echo $row['title']; ?></h3>
                                    <p class="service-card__provider">By: <?php echo $row['provider_name']; ?></p>
                                    <p class="service-card__provider">Category: <?php echo htmlspecialchars($row['category']); ?></p>
                                    <p class="service-card__price">$<?php echo $row['price']; ?></p>
                                    <a href="service_detail.php?id=<?php echo $row['id']; ?>" class="btn btn--primary">View Details</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>No services match this selection.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>
</body>
</html>