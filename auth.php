<?php
require_once __DIR__ . "/config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_admin(): bool
{
    return !empty($_SESSION["admin_logged_in"]);
}

function require_admin(): void
{
    // For AJAX requests, return JSON error
    if (is_ajax_request()) {
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode([
            "ok" => false,
            "message" => "Admin login required."
        ]);
        exit;
    }

    // For normal requests, go back home and open modal
    $next = $_SERVER["REQUEST_URI"] ?? "";
    redirect("?login=1&next=" . urlencode($next));
}