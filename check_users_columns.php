<?php
require 'includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

$result = $conn->query("DESCRIBE users");
echo "Users table columns:\n";
while ($row = $result->fetch_assoc()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
