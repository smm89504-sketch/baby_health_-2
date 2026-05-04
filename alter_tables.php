<?php
require_once 'includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// Add missing columns to users table
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS clinic_address VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS experience_years INT DEFAULT NULL");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS address VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS latitude DECIMAL(10,6) DEFAULT NULL");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS longitude DECIMAL(10,6) DEFAULT NULL");

// Add is_twin column to children table (optional, for future use)
$conn->query("ALTER TABLE children ADD COLUMN IF NOT EXISTS is_twin TINYINT(1) DEFAULT 0");

// Add gender column to children table (optional)
$conn->query("ALTER TABLE children ADD COLUMN IF NOT EXISTS gender ENUM('male','female') DEFAULT NULL");

echo "Columns added successfully!";
?>