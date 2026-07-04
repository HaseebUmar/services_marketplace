<?php
session_start();
include("../config/db.php");

$id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Purana data fetch karein
$stmt = $conn->prepare("SELECT * FROM services WHERE id=? AND user_id=?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$service = $stmt->get_result()->fetch_assoc();

if(isset($_POST['update_service'])){
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $price = $_POST['price'];
    $city = mysqli_real_escape_string($conn, $_POST['city'] ?? '');
    $area = mysqli_real_escape_string($conn, $_POST['area'] ?? '');

    $update_stmt = $conn->prepare("UPDATE services SET title=?, description=?, price=?, city=?, area=? WHERE id=?");
    $update_stmt->bind_param("sssdsi", $title, $desc, $price, $city, $area, $id);
    $update_stmt->execute();
    header("Location: dashboard.php");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Service</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/provider.css">
</head>
<body>
    <div class="form-container">
        <div class="form-card">
            <h2>Edit Service</h2>
            <p class="form-card__subtitle">Updating: <strong><?php echo htmlspecialchars($service['title']); ?></strong></p>
            <form method="POST" class="form-card__form">
                <label>
                    Service Title
                    <input type="text" name="title" value="<?php echo htmlspecialchars($service['title']); ?>" required>
                </label>
                <label>
                    Description
                    <textarea name="description" required><?php echo htmlspecialchars($service['description']); ?></textarea>
                </label>
                <label>
                    Price ($)
                    <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($service['price']); ?>" required>
                </label>
                <div class="provider-locked-info">
                    <p><strong>Service Type:</strong> <?php echo ucfirst($service['service_type']); ?></p>
                    <p><strong>Category:</strong> <?php echo htmlspecialchars($service['category']); ?></p>
                </div>
                <div id="location_fields" style="display: <?php echo ($service['service_type'] == 'local') ? 'block' : 'none'; ?>;">
                    <label>
                        City
                        <input type="text" name="city" value="<?php echo htmlspecialchars($service['city']); ?>">
                    </label>
                    <label>
                        Area
                        <input type="text" name="area" value="<?php echo htmlspecialchars($service['area']); ?>">
                    </label>
                </div>
                <button name="update_service" class="btn btn--primary">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('location_fields').style.display = '<?php echo ($service['service_type'] == 'local') ? 'block' : 'none'; ?>';
        });
    </script>
</body>
</html>