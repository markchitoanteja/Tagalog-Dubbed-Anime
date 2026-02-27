<?php
require "db.php";
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/auth.php";

header("Content-Type: application/json; charset=utf-8");

$username = trim($_POST["username"] ?? "");
$password = (string)($_POST["password"] ?? "");

if ($username === "" || $password === "") {
    echo json_encode(["ok" => false, "message" => "Username and password are required."]);
    exit;
}

$stmt = $conn->prepare("SELECT id, password_hash FROM admins WHERE username=? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row || !password_verify($password, $row["password_hash"])) {
    echo json_encode(["ok" => false, "message" => "Invalid username or password."]);
    exit;
}

$_SESSION["admin_logged_in"] = true;
$_SESSION["admin_username"] = $username;

$next = trim($_POST["next"] ?? "");
echo json_encode([
    "ok" => true,
    "redirect" => $next !== "" ? $next : base_url()
]);
