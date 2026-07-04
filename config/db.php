<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "services_marketplace";

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Auto-migrate missing provider and service columns if needed
$schemaUpdates = [
    'users' => [
        'provider_type' => "ENUM('online','local') DEFAULT NULL",
        'provider_category' => 'VARCHAR(100) DEFAULT NULL',
        'contact_phone' => 'VARCHAR(50) DEFAULT NULL'
    ],
    'services' => [
        'category' => 'VARCHAR(100) DEFAULT NULL'
    ]
];

foreach ($schemaUpdates as $table => $columns) {
    foreach ($columns as $column => $definition) {
        $check = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . $conn->real_escape_string($db) . "' AND TABLE_NAME = '" . $conn->real_escape_string($table) . "' AND COLUMN_NAME = '" . $conn->real_escape_string($column) . "'");
        if($check && $check->num_rows === 0) {
            $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        }
    }
}
?>