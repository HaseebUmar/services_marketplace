<?php
session_start();
include("../config/db.php");

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider'){
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : null;
$message_text = "";

if(isset($_POST['send_reply']) && $customer_id){
    $message_text = mysqli_real_escape_string($conn, $_POST['message']);
    $subject = "Platform Chat";

    $subject_stmt = $conn->prepare("SELECT subject FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC LIMIT 1");
    $subject_stmt->bind_param("iiii", $user_id, $customer_id, $customer_id, $user_id);
    $subject_stmt->execute();
    $subject_result = $subject_stmt->get_result();
    if($subject_row = $subject_result->fetch_assoc()){
        $subject = $subject_row['subject'];
    }

    $insert_stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");
    $insert_stmt->bind_param("iiss", $user_id, $customer_id, $subject, $message_text);
    if($insert_stmt->execute()){
        header("Location: messages.php?customer_id=" . $customer_id);
        exit();
    }
}

$partners_query = "SELECT u.id, u.username, u.email, MAX(m.created_at) as last_message,
    SUM(IF(m.receiver_id = ? AND m.is_read = 0, 1, 0)) as unread_count,
    SUBSTRING_INDEX(GROUP_CONCAT(m.message ORDER BY m.created_at DESC SEPARATOR '|||'), '|||', 1) as last_text
    FROM messages m
    JOIN users u ON u.id = IF(m.sender_id = ?, m.receiver_id, m.sender_id)
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY u.id
    ORDER BY last_message DESC";

$partners_stmt = $conn->prepare($partners_query);
$partners_stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$partners_stmt->execute();
$partners = $partners_stmt->get_result();

$conversation = null;
$customer = null;
if($customer_id){
    $customer_stmt = $conn->prepare("SELECT id, username FROM users WHERE id = ? AND role = 'customer'");
    $customer_stmt->bind_param("i", $customer_id);
    $customer_stmt->execute();
    $customer = $customer_stmt->get_result()->fetch_assoc();

    if($customer){
        $chat_query = "SELECT m.*, u.username as sender_name FROM messages m JOIN users u ON m.sender_id = u.id WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?) ORDER BY m.created_at ASC";
        $chat_stmt = $conn->prepare($chat_query);
        $chat_stmt->bind_param("iiii", $user_id, $customer_id, $customer_id, $user_id);
        $chat_stmt->execute();
        $conversation = $chat_stmt->get_result();

        $read_stmt = $conn->prepare("UPDATE messages SET is_read = TRUE WHERE receiver_id = ? AND sender_id = ? AND is_read = FALSE");
        $read_stmt->bind_param("ii", $user_id, $customer_id);
        $read_stmt->execute();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | ServiceHub</title>
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
            <a href="messages.php" class="active"><i class='bx bxs-envelope'></i> Messages</a>
            <a href="earnings.php"><i class='bx bxs-wallet'></i> Earnings</a>
            <div class="sidebar__divider"></div>
            <a href="../auth/logout.php" class="logout"><i class='bx bx-log-out'></i> Logout</a>
        </nav>
    </aside>

    <main class="dashboard-main">
        <header class="top-bar">
            <div class="top-bar__search">
                <i class='bx bx-search'></i>
                <input type="text" placeholder="Search chats..." id="search-messages">
            </div>
            <div class="top-bar__profile">
                <div class="profile-info">
                    <p>Welcome back,</p>
                    <strong><?php echo $_SESSION['username']; ?></strong>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?php echo $_SESSION['username']; ?>&background=random" alt="Avatar">
            </div>
        </header>

        <section class="listing-section">
            <div class="listing-header">
                <h2>Customer Chats</h2>
            </div>

            <div class="chat-layout">
                <div class="conversation-list">
                    <div class="conversation-list__top">
                        <h3>Conversations</h3>
                    </div>
                    <?php if($partners->num_rows > 0): ?>
                        <?php while($partner = $partners->fetch_assoc()): ?>
                            <a href="messages.php?customer_id=<?php echo $partner['id']; ?>" class="conversation-link <?php echo ($customer_id == $partner['id']) ? 'active' : ''; ?>">
                                <div>
                                    <strong><?php echo $partner['username']; ?></strong>
                                    <p><?php echo htmlspecialchars(substr($partner['last_text'], 0, 50)); ?></p>
                                </div>
                                <?php if($partner['unread_count'] > 0): ?>
                                    <span class="badge-count"><?php echo $partner['unread_count']; ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class='bx bx-chat'></i>
                            <p>No conversations yet. Customers will message you here when they want to chat.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="conversation-panel">
                    <?php if($customer): ?>
                        <div class="chat-header">
                            <div>
                                <h3><?php echo $customer['username']; ?></h3>
                                <small>Chat with your customer</small>
                            </div>
                        </div>

                        <div class="message-thread" id="message-thread">
                            <?php if($conversation && $conversation->num_rows > 0): ?>
                                <?php while($chat = $conversation->fetch_assoc()): ?>
                                    <div class="chat-item <?php echo $chat['sender_id'] == $user_id ? 'chat-item--sent' : 'chat-item--received'; ?>">
                                        <div class="chat-item__meta">
                                            <span><?php echo htmlspecialchars($chat['sender_name']); ?></span>
                                            <small><?php echo date('M d, Y H:i', strtotime($chat['created_at'])); ?></small>
                                        </div>
                                        <p><?php echo nl2br(htmlspecialchars($chat['message'])); ?></p>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class='bx bx-comment-dots'></i>
                                    <p>Start chatting with <?php echo $customer['username']; ?>.</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <form method="POST" class="chat-form">
                            <textarea name="message" placeholder="Type your reply..." required></textarea>
                            <button type="submit" name="send_reply" class="btn btn--primary">Send Reply</button>
                        </form>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class='bx bx-chat'></i>
                            <p>Select a conversation to open the chat thread.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <script>
        document.getElementById('search-messages').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const items = document.querySelectorAll('.conversation-link');

            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    </script>
</body>
</html>