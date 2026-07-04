<?php
session_start();
include("../config/db.php");

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer'){
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$provider_id = isset($_GET['provider_id']) ? intval($_GET['provider_id']) : null;
$message = "";

if(isset($_POST['send_message']) && $provider_id){
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $message_text = mysqli_real_escape_string($conn, $_POST['message']);
    $msg_stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");
    $msg_stmt->bind_param("iiss", $user_id, $provider_id, $subject, $message_text);

    if($msg_stmt->execute()){
        header("Location: messages.php?provider_id=" . $provider_id);
        exit();
    } else {
        $message = "<div class='alert alert--error'>Failed to send the message. Please try again.</div>";
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
$provider = null;
if($provider_id){
    $provider_stmt = $conn->prepare("SELECT id, username FROM users WHERE id = ? AND role = 'provider'");
    $provider_stmt->bind_param("i", $provider_id);
    $provider_stmt->execute();
    $provider = $provider_stmt->get_result()->fetch_assoc();

    if($provider){
        $chat_query = "SELECT m.*, u.username as sender_name FROM messages m JOIN users u ON m.sender_id = u.id WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?) ORDER BY m.created_at ASC";
        $chat_stmt = $conn->prepare($chat_query);
        $chat_stmt->bind_param("iiii", $user_id, $provider_id, $provider_id, $user_id);
        $chat_stmt->execute();
        $conversation = $chat_stmt->get_result();

        $read_stmt = $conn->prepare("UPDATE messages SET is_read = TRUE WHERE receiver_id = ? AND sender_id = ? AND is_read = FALSE");
        $read_stmt->bind_param("ii", $user_id, $provider_id);
        $read_stmt->execute();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Chats | ServiceHub</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/customer.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="dashboard-body">
    <main class="dashboard-main" style="width:100%; padding: 2rem;">
        <section class="listing-section">
            <div class="listing-header">
                <h2>My Chats</h2>
            </div>

            <div class="chat-layout">
                <div class="conversation-list">
                    <div class="conversation-list__top">
                        <h3>Conversations</h3>
                    </div>
                    <?php if($partners->num_rows > 0): ?>
                        <?php while($partner = $partners->fetch_assoc()): ?>
                            <a href="messages.php?provider_id=<?php echo $partner['id']; ?>" class="conversation-link <?php echo ($provider_id == $partner['id']) ? 'active' : ''; ?>">
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
                            <p>No conversations yet. Send a message from a provider profile to start chatting.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="conversation-panel">
                    <?php if($provider): ?>
                        <div class="chat-header">
                            <div>
                                <h3><?php echo $provider['username']; ?></h3>
                                <small>Chat with your provider</small>
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
                                    <p>Start your conversation with <?php echo $provider['username']; ?>.</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <form method="POST" class="chat-form">
                            <input type="hidden" name="provider_id" value="<?php echo $provider['id']; ?>">
                            <textarea name="message" placeholder="Type your message..." required></textarea>
                            <button type="submit" name="send_message" class="btn btn--primary">Send Message</button>
                        </form>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class='bx bx-chat'></i>
                            <p>Select a conversation to view the chat thread.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
</body>
</html>