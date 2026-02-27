<?php
require "db.php";

// Change these BEFORE running
$username = "admin";
$password = "admin123";

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $hash);

if ($stmt->execute()) {
    echo "Admin created! Username: {$username} Password: {$password}<br>";
    echo "DELETE this file (create_admin.php) after creating the admin.";
} else {
    echo "Error: " . $conn->error . "<br>";
    echo "If admin already exists, that's okay.";
}
$stmt->close();
