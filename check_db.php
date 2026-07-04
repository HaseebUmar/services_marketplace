<?php
include("config/db.php");

// Check if messages table exists
$result = $conn->query("SHOW TABLES LIKE 'messages'");
if($result->num_rows > 0){
    echo "✅ Messages table exists!\n";
} else {
    echo "❌ Messages table not found\n";
}

// Check all tables
echo "\nAll tables in database:\n";
$tables = $conn->query("SHOW TABLES");
while($row = $tables->fetch_array()){
    echo "- " . $row[0] . "\n";
}
?>