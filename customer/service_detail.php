<?php
session_start();
include("../config/db.php");

if(!isset($_GET['id'])){ header("Location: browse.php"); exit(); }

$service_id = $_GET['id'];
$message = "";

// 1. Service details fetch karein
$stmt = $conn->prepare("SELECT s.*, u.id as provider_id, u.username as provider, u.email as provider_email, u.contact_phone as provider_phone, u.provider_type as provider_type, u.provider_category as provider_category FROM services s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$service = $stmt->get_result()->fetch_assoc();


if(isset($_POST['place_order']) && isset($_SESSION['role']) && $_SESSION['role'] == 'customer'){

    $customer_id = $_SESSION['user_id'];
    $provider_id = $service['provider_id'];

    // duplicate order check
    $check = $conn->prepare("SELECT id FROM orders WHERE service_id=? AND customer_id=?");
    $check->bind_param("ii", $service_id, $customer_id);
    $check->execute();
    $check_result = $check->get_result();

    if($check_result->num_rows > 0){
        $message = "<div class='alert alert--error'>You already placed this order</div>";
    } else {

        $stmt = $conn->prepare("INSERT INTO orders (service_id, customer_id, provider_id, status) VALUES (?, ?, ?, 'pending')");
        $stmt->bind_param("iii", $service_id, $customer_id, $provider_id);

        if($stmt->execute()){
            $message = "<div class='alert alert--success'>Order placed successfully!</div>";
        } else {
            $message = "<div class='alert alert--error'>Order failed</div>";
        }
    }
}

// 2. Feedback handle karein (POST request)
if(isset($_POST['submit_feedback']) && isset($_SESSION['role']) && $_SESSION['role'] == 'customer'){
    $user_id = $_SESSION['user_id'];
    $rating = $_POST['rating'];
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);

    $f_stmt = $conn->prepare("INSERT INTO feedback (service_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
    $f_stmt->bind_param("iiis", $service_id, $user_id, $rating, $comment);
    $f_stmt->execute();
    header("Location: service_detail.php?id=$service_id&success=1");
    exit();
}

if(isset($_POST['send_message']) && isset($_SESSION['role']) && $_SESSION['role'] == 'customer'){
    $customer_id = $_SESSION['user_id'];
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $message_text = mysqli_real_escape_string($conn, $_POST['message']);
    $provider_id = $service['provider_id'];

    $msg_stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");
    $msg_stmt->bind_param("iiss", $customer_id, $provider_id, $subject, $message_text);

    if($msg_stmt->execute()){
        $message = "<div class='alert alert--success'>Message sent successfully! You can continue the conversation in Messages.</div>";
    } else {
        $message = "<div class='alert alert--error'>Failed to send your message. Please try again.</div>";
    }
}

// 3. Average Rating calculate karein
$avg_res = $conn->query("SELECT AVG(rating) as avg FROM feedback WHERE service_id = $service_id");
$avg_row = $avg_res->fetch_assoc();
$average = number_format($avg_row['avg'], 1);

// 4. All Feedbacks fetch karein
$feedbacks = $conn->query("SELECT f.*, u.username FROM feedback f JOIN users u ON f.user_id = u.id WHERE f.service_id = $service_id ORDER BY f.id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $service['title']; ?> | Details</title>

    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/customer.css">
</head>

<body>

<nav class="navbar">
    <div class="navbar__logo">ServiceHub</div>
    <div class="navbar__links"><a href="browse.php">Back to Browse</a></div>
</nav>

<main class="container">

<div class="detail-grid">

    <!-- LEFT SIDE -->
    <div class="detail-card">

        <div class="detail-card__media">
            <img src="../uploads/<?php echo $service['image']; ?>" class="detail-card__img">
        </div>

        <div class="detail-card__body">

            <span class="badge badge--primary">
                ⭐ <?php echo ($average > 0) ? $average : "No Rating"; ?>
            </span>

            <h1 class="detail-card__title"><?php echo $service['title']; ?></h1>

            <p class="detail-card__desc"><?php echo $service['description']; ?></p>

            <div class="detail-card__meta">
                <p><strong>Price:</strong> $<?php echo $service['price']; ?></p>
                <p><strong>Type:</strong> <?php echo ucfirst($service['service_type']); ?></p>
                <p><strong>Category:</strong> <?php echo htmlspecialchars($service['category']); ?></p>

                <p>
                    <strong>Provider:</strong>
                    <a href="provider_profile.php?id=<?php echo $service['user_id']; ?>">
                        <?php echo $service['provider']; ?>
                    </a>
                </p>

                <?php if($service['provider_email']): ?>
                    <p><strong>Email:</strong> <?php echo $service['provider_email']; ?></p>
                <?php endif; ?>

                <?php if($service['provider_phone']): ?>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($service['provider_phone']); ?></p>
                <?php endif; ?>

                <?php if($service['service_type'] == 'local'): ?>
                    <p><strong>Location:</strong> <?php echo $service['area']; ?>, <?php echo $service['city']; ?></p>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- RIGHT SIDE (STICKY BOOKING PANEL) -->
    <aside class="booking-panel">

        <div class="booking-card">

            <div class="booking-price">
                $<?php echo $service['price']; ?>
                <small>/ service</small>
            </div>

            <div class="booking-info">
                <p>✔ Verified Provider</p>
                <p>✔ Fast Response</p>
                <p>✔ Secure Messaging</p>
            </div>

            <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'customer'): ?>

                <div class="message-box">

                    <h3>Send Message</h3>

                    <?php echo $message; ?>

                    <form method="POST" class="auth-card__form">

                        <input type="text" name="subject"
                            class="auth-card__input"
                            value="Inquiry about <?php echo htmlspecialchars($service['title']); ?>">

                        <textarea name="message"
                            class="auth-card__input"
                            rows="4"
                            placeholder="Describe your request..."></textarea>

                        <button type="submit" name="send_message"
                            class="btn btn--primary btn--full">
                            Send Message
                        </button>

                    </form>
                    </form>
    <form method="POST" style="margin-top:15px;margin-bottom:15px;">
    <button type="submit" name="place_order" class="btn btn--primary btn--full">
        Book Service
    </button>
</form>

                </div>

            <?php else: ?>

                <p>Please login to contact provider</p>

            <?php endif; ?>

        </div>

    </aside>

</div>

<!-- FEEDBACK (UNCHANGED) -->
<div class="feedback-section">

    <h3>Submit Feedback</h3>

    <form method="POST" class="auth-card__form">
        <select name="rating" class="auth-card__select" required>
            <option value="5">5 Stars</option>
            <option value="4">4 Stars</option>
            <option value="3">3 Stars</option>
            <option value="2">2 Stars</option>
            <option value="1">1 Star</option>
        </select>

        <textarea name="comment" class="auth-card__input" required></textarea>

        <button name="submit_feedback" class="auth-card__button">
            Post Review
        </button>
    

    <hr class="separator">

    <h3>Reviews</h3>

    <div class="reviews-list">
        <?php while($f = $feedbacks->fetch_assoc()): ?>
            <div class="review-item">
                <strong><?php echo $f['username']; ?></strong>
                <span>⭐ <?php echo $f['rating']; ?>/5</span>
                <p><?php echo $f['comment']; ?></p>
                <small><?php echo date('M d, Y', strtotime($f['created_at'])); ?></small>
            </div>
        <?php endwhile; ?>
    </div>

</div>

</main>

</body>
</html>