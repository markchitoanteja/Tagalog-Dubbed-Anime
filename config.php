<?php
function base_url(string $path = ""): string
{
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

    $scheme = $isHttps ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? "localhost";

    $project = "Tagalog-Dubbed-Anime";

    $path = ltrim($path, "/");
    return $scheme . "://" . $host . "/" . $project . ($path !== "" ? "/" . $path : "");
}

function redirect(string $path = ""): void
{
    // If $path starts with "?" treat as query string for home
    if (strlen($path) > 0 && $path[0] === "?") {
        header("Location: " . base_url() . $path);
        exit;
    }
    header("Location: " . base_url($path));
    exit;
}

function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
}

function is_ajax_request(): bool
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}
