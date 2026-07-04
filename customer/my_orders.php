<?php
session_start();
include("../config/db.php");

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer'){
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];

$query = "SELECT 
    o.id AS order_id,
    o.status,
    o.created_at,
    o.quantity,
    o.total_price,

    s.title AS service_title,
    s.image,

    u.username AS provider_name

FROM orders o
JOIN services s ON o.service_id = s.id
JOIN users u ON o.provider_id = u.id

WHERE o.customer_id = ?
ORDER BY o.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Orders</title>
    <style>

  body {
    font-family: Arial;
    background: #f4f6f9;
    margin: 0;
    padding: 0;
}

/* FIXED CONTAINER */
.container {
    max-width: 1200px;
    margin: auto;
    padding: 20px;
}

/* GRID SYSTEM */
.grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

/* CARD */
.card {
    background: #fff;
    border-radius: 12px;
    padding: 15px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.05);
    transition: 0.3s;
}

.card:hover {
    transform: translateY(-5px);
}

/* IMAGE FIX */
.card img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    display: block;
    border-radius: 10px;
}

/* TEXT */
.title {
    font-size: 18px;
    margin: 10px 0;
    font-weight: bold;
}

.meta {
    font-size: 14px;
    color: #555;
    margin-bottom: 5px;
}

/* STATUS */
.status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    margin-top: 8px;
}

/* COLORS */
.pending { background: #fff3cd; color: #856404; }
.accepted { background: #cce5ff; color: #004085; }
.handed_over { background: #e2d9f3; color: #4b0082; }
.completed { background: #d4edda; color: #155724; }
.cancelled { background: #f8d7da; color: #721c24; }

/* DATE */
.date {
    font-size: 12px;
    color: #888;
    margin-top: 5px;
}

/* TOP BAR */
.top-bar {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}

/* PREMIUM BACK BUTTON */
.back-btn {
    text-decoration: none;
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    color: #fff;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    box-shadow: 0 6px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

/* HOVER EFFECT */
.back-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.15);
    background: linear-gradient(135deg, #4338ca, #6d28d9);
}

/* ACTIVE CLICK */
.back-btn:active {
    transform: scale(0.96);
}
</style>
</head>

<body>
<div class="top-bar">
    <a href="browse.php" class="back-btn">
        ⬅ Back to Browse
    </a>
</div>
<div class="container">

    <h2>My Orders</h2>


    <div class="grid">

        <?php if($result->num_rows > 0): ?>

            <?php while($row = $result->fetch_assoc()): ?>

                <div class="card">

                    <img src="../uploads/<?php echo $row['image']; ?>">

                    <div class="title"><?php echo $row['service_title']; ?></div>

                    <div class="meta">Provider: <?php echo $row['provider_name']; ?></div>
                    <div class="meta">Quantity: <?php echo $row['quantity']; ?></div>
                    <div class="meta">Total: $<?php echo $row['total_price']; ?></div>

                    <div class="status <?php echo $row['status']; ?>">
                        <?php echo strtoupper(str_replace('_',' ', $row['status'])); ?>
                    </div>

                    <div class="date">
                        Ordered: <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                    </div>

                </div>
                

            <?php endwhile; ?>

        <?php else: ?>

            <p>No orders found</p>

        <?php endif; ?>

    </div>

</div>

</body>
</html>