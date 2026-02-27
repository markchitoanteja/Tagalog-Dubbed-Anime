<?php
$conn = new mysqli("localhost", "root", "", "tagalog_dubbed_anime");
if ($conn->connect_error) {
    die("DB Error: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
