<?php
session_start();
include("../config/db.php");

if(isset($_GET['id']) && $_SESSION['role'] == 'provider'){
    $id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("DELETE FROM services WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
}
header("Location: dashboard.php");
exit();
?>