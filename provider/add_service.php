<?php
session_start();
include("../config/db.php");

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider'){
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// Load provider registration settings and service count
$provider_stmt = $conn->prepare("SELECT provider_type, provider_category FROM users WHERE id = ? AND role = 'provider'");
$provider_stmt->bind_param("i", $user_id);
$provider_stmt->execute();
$provider = $provider_stmt->get_result()->fetch_assoc();

$service_check = $conn->prepare("SELECT COUNT(*) as service_count FROM services WHERE user_id = ?");
$service_check->bind_param("i", $user_id);
$service_check->execute();
$service_count = $service_check->get_result()->fetch_assoc()['service_count'];

if($service_count > 0){
    header("Location: dashboard.php?notice=one_service_only");
    exit();
}

$provider_settings_ok = $provider && !empty($provider['provider_type']) && !empty($provider['provider_category']);

if(!$provider_settings_ok){
    $message = "<div class='alert'>Complete your provider profile first.</div>";
}

if(isset($_POST['add_service']) && $provider_settings_ok){
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $price = $_POST['price'];
    $type = $provider['provider_type'];
    $category = mysqli_real_escape_string($conn, $provider['provider_category']);
    $city = mysqli_real_escape_string($conn, $_POST['city'] ?? '');
    $area = mysqli_real_escape_string($conn, $_POST['area'] ?? '');
    $deadline = $_POST['deadline'];

    $final_image = "";

    if(!empty($_FILES['image']['name'])){
        $image_name = time() . "_" . basename($_FILES['image']['name']);
        $target = "../uploads/" . $image_name;

        if(move_uploaded_file($_FILES['image']['tmp_name'], $target)){
            $final_image = $image_name;
        }
    }

    $stmt = $conn->prepare("INSERT INTO services (user_id, title, description, price, image, service_type, category, city, area, deadline) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issdssssss", $user_id, $title, $desc, $price, $final_image, $type, $category, $city, $area, $deadline);
    
    if($stmt->execute()){
        header("Location: dashboard.php");
        exit();
    } else {
        $message = "<div class='alert'>Something went wrong!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Post Service</title>

<style>
body{
    margin:0;
    font-family:Segoe UI;
    background: linear-gradient(135deg,#0f172a,#1e293b);
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:100vh;
}

/* CARD */
.card{
    width:750px;
    padding:40px;
    border-radius:20px;
    background: rgba(255,255,255,0.06);
    backdrop-filter: blur(20px);
    border:1px solid rgba(255,255,255,0.1);
    box-shadow:0 25px 60px rgba(0,0,0,0.5);
    color:white;
}

/* TITLE */
h2{
    margin-bottom:10px;
    font-size:28px;
}

.subtitle{
    color:#94a3b8;
    margin-bottom:25px;
}

/* INPUTS */
input, textarea{
    width:100%;
    padding:14px;
    margin-bottom:15px;
    border-radius:12px;
    border:none;
    outline:none;
    background:rgba(255,255,255,0.08);
    color:white;
    transition:0.3s;
}

input:focus, textarea:focus{
    background:rgba(255,255,255,0.15);
    transform:scale(1.02);
}

textarea{resize:none}

/* ROW */
.row{
    display:flex;
    gap:15px;
}

/* BUTTON */
button{
    width:100%;
    padding:14px;
    border:none;
    border-radius:12px;
    background:linear-gradient(135deg,#6366f1,#4f46e5);
    color:white;
    font-weight:bold;
    cursor:pointer;
    transition:0.3s;
}

button:hover{
    transform:translateY(-2px);
}

/* ALERT */
.alert{
    background:rgba(239,68,68,0.2);
    padding:10px;
    border-radius:10px;
    margin-bottom:15px;
    color:#fca5a5;
}

/* INFO BOX */
.info{
    background:rgba(255,255,255,0.05);
    padding:12px;
    border-radius:12px;
    margin-bottom:15px;
    color:#cbd5e1;
}

/* LINK */
a{
    color:#94a3b8;
    display:block;
    text-align:center;
    margin-top:15px;
    text-decoration:none;
}
a:hover{color:white}

@media(max-width:768px){
    .card{width:90%}
    .row{flex-direction:column}
}
</style>
</head>

<body>

<div class="card">

<h2>🚀 Post Your Service</h2>
<p class="subtitle">Start earning by offering your skills</p>

<?php echo $message; ?>

<?php if($provider_settings_ok): ?>

<form method="POST" enctype="multipart/form-data">

<input type="text" name="title" placeholder="Service Title (e.g. Web Design)" required>

<textarea name="description" rows="5" placeholder="Describe your service..." required></textarea>

<div class="row">
    <input type="number" step="0.5" name="price" placeholder="Price ($)" required>
    <input type="date" name="deadline" required>
</div>

<div class="info">
    <b>Type:</b> <?php echo ucfirst($provider['provider_type']); ?><br>
    <b>Category:</b> <?php echo htmlspecialchars($provider['provider_category']); ?>
</div>

<div class="row">
    <input type="file" name="image">
</div>

<?php if($provider['provider_type'] == 'local'): ?>
<div class="row">
    <input type="text" name="city" placeholder="City">
    <input type="text" name="area" placeholder="Area">
</div>
<?php endif; ?>

<button name="add_service">Publish Service 🚀</button>

</form>

<?php else: ?>

<div class="alert">
Complete your provider profile before posting a service.
</div>

<?php endif; ?>

<a href="dashboard.php">← Back to Dashboard</a>

</div>

</body>
</html>