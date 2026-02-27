<?php
require "db.php";
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/auth.php";
if (!is_admin()) require_admin();

// Set higher execution time for large files
set_time_limit(3600);
ini_set('max_execution_time', 3600);

// Detect if this is an AJAX request
function is_ajax(): bool
{
    return (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
}

function fail(string $msg): void
{
    if (is_ajax()) {
        header('Content-Type: application/json');
        echo json_encode(["ok" => false, "message" => $msg]);
    } else {
        // Fallback for non-AJAX direct form submission
        echo "<div style='max-width:900px;margin:20px auto;font-family:system-ui;color:#fff;background:#111827;border:1px solid #1f2937;border-radius:12px;padding:18px;'>";
        echo "<h3 style='margin:0 0 10px 0;'><i class='fa-solid fa-triangle-exclamation'></i> Upload Error</h3>";
        echo "<div>" . h($msg) . "</div>";
        echo "<div style='margin-top:12px;'><a href='" . base_url("upload") . "' style='color:#6366f1;'>Go back</a></div>";
        echo "</div>";
    }
    exit;
}

// Check REQUEST_METHOD
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail("Invalid request method.");
}

// Check for file upload errors (including size)
if (!isset($_FILES["video"])) {
    fail("No file uploaded.");
}

$uploadErr = $_FILES["video"]["error"];

// Handle different upload error codes
if ($uploadErr === UPLOAD_ERR_INI_SIZE) {
    fail("File size exceeds server's upload_max_filesize limit. Please use a smaller file.");
}
if ($uploadErr === UPLOAD_ERR_FORM_SIZE) {
    fail("File size exceeds form MAX_FILE_SIZE limit. Please use a smaller file.");
}
if ($uploadErr === UPLOAD_ERR_PARTIAL) {
    fail("File upload was incomplete. Please try again.");
}
if ($uploadErr === UPLOAD_ERR_NO_FILE) {
    fail("No file was selected.");
}
if ($uploadErr === UPLOAD_ERR_NO_TMP_DIR) {
    fail("Server temp directory is missing.");
}
if ($uploadErr === UPLOAD_ERR_CANT_WRITE) {
    fail("Cannot write to server disk.");
}
if ($uploadErr === UPLOAD_ERR_EXTENSION) {
    fail("File upload stopped by PHP extension.");
}
if ($uploadErr !== UPLOAD_ERR_OK) {
    fail("Unknown file upload error. Code: $uploadErr");
}

// Validate POST fields
$seriesTitle = trim($_POST["series_title"] ?? "");
$season = $_POST["season"] ?? null;
$episode = $_POST["episode"] ?? null;
$description = trim($_POST["description"] ?? "");

if ($seriesTitle === "") {
    fail("Series title is required.");
}

if ($season === null || $season === "" || !ctype_digit((string)$season) || (int)$season < 1) {
    fail("Season is required and must be >= 1.");
}

if ($episode === null || $episode === "" || !ctype_digit((string)$episode) || (int)$episode < 1) {
    fail("Episode is required and must be >= 1.");
}

$season = (int)$season;
$episode = (int)$episode;

// Setup directories
$videoDir = __DIR__ . "/uploads/videos/";
$thumbDir = __DIR__ . "/uploads/thumbs/";
$logsDir = __DIR__ . "/logs";

// Create directories if they don't exist
if (!is_dir($videoDir)) {
    if (!mkdir($videoDir, 0777, true)) {
        fail("Could not create video upload directory.");
    }
}

if (!is_dir($thumbDir)) {
    if (!mkdir($thumbDir, 0777, true)) {
        fail("Could not create thumbnail directory.");
    }
}

if (!is_dir($logsDir)) {
    if (!mkdir($logsDir, 0777, true)) {
        fail("Could not create logs directory.");
    }
}

// Check write permissions
if (!is_writable($videoDir)) {
    fail("Video folder is not writable: $videoDir");
}

if (!is_writable($thumbDir)) {
    fail("Thumbnail folder is not writable: $thumbDir");
}

if (!is_writable($logsDir)) {
    fail("Logs folder is not writable: $logsDir");
}

// Validate file extension
$originalName = $_FILES["video"]["name"];
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$allowed = ["mp4", "mkv", "mov", "avi", "webm", "flv", "wmv", "m4v"];

if (!in_array($ext, $allowed, true)) {
    fail("Invalid format. Allowed: " . implode(", ", $allowed));
}

// Find or create series
$stmt = $conn->prepare("SELECT id FROM series WHERE title = ?");
if (!$stmt) {
    fail("Database error: " . $conn->error);
}

$stmt->bind_param("s", $seriesTitle);
if (!$stmt->execute()) {
    fail("Database error: " . $conn->error);
}

$found = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($found) {
    $seriesId = (int)$found["id"];
} else {
    $stmt = $conn->prepare("INSERT INTO series (title) VALUES (?)");
    if (!$stmt) {
        fail("Database error: " . $conn->error);
    }

    $stmt->bind_param("s", $seriesTitle);
    if (!$stmt->execute()) {
        fail("Could not create series. Maybe duplicate title? Error: " . $conn->error);
    }
    $seriesId = (int)$stmt->insert_id;
    $stmt->close();
}

// Prevent duplicates
$stmt = $conn->prepare("SELECT id FROM videos WHERE series_id = ? AND season = ? AND episode = ?");
if (!$stmt) {
    fail("Database error: " . $conn->error);
}

$stmt->bind_param("iii", $seriesId, $season, $episode);
if (!$stmt->execute()) {
    fail("Database error: " . $conn->error);
}

$dup = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($dup) {
    fail("This episode already exists (Season $season, Episode $episode).");
}

// Save video file
$baseName = "ep_" . date("Ymd_His") . "_" . bin2hex(random_bytes(4));
$videoFile = $baseName . "." . $ext;
$thumbFile = $baseName . ".jpg";

$videoFullPath = $videoDir . $videoFile;
$thumbFullPath = $thumbDir . $thumbFile;

// Move uploaded file
if (!move_uploaded_file($_FILES["video"]["tmp_name"], $videoFullPath)) {
    fail("Could not save the uploaded video file. Check folder permissions.");
}

// Ensure file is readable
if (!is_readable($videoFullPath)) {
    @unlink($videoFullPath);
    fail("Uploaded file is not readable. Check permissions.");
}

// FFmpeg path (customize for your system)
$ffmpeg = "C:\\ffmpeg\\ffmpeg-6.x-essentials_build\\bin\\ffmpeg.exe";

// Check if FFmpeg exists
if (!file_exists($ffmpeg)) {
    @unlink($videoFullPath);
    fail("FFmpeg not found at: $ffmpeg");
}

// Extract thumbnail at 5 seconds
$time = "00:00:05";
$cmd = '"' . $ffmpeg . '"' .
    ' -y -ss ' . escapeshellarg($time) .
    ' -i ' . escapeshellarg($videoFullPath) .
    ' -vframes 1 -q:v 2 ' . escapeshellarg($thumbFullPath) .
    ' 2>&1';

$output = shell_exec($cmd);
$logMessage = "=== Upload: " . date("Y-m-d H:i:s") . " ===\n";
$logMessage .= "File: " . $originalName . "\n";
$logMessage .= "Command: $cmd\n";
$logMessage .= "Output: $output\n";
$logMessage .= str_repeat("-", 80) . "\n\n";

@file_put_contents($logsDir . "/ffmpeg_log.txt", $logMessage, FILE_APPEND);

// Prepare web paths
$videoWebPath = "uploads/videos/" . $videoFile;
$thumbWebPath = file_exists($thumbFullPath) ? ("uploads/thumbs/" . $thumbFile) : "assets/placeholder.png";

// Insert episode into database
$stmt = $conn->prepare("INSERT INTO videos (series_id, season, episode, description, video_path, thumb_path) VALUES (?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    @unlink($videoFullPath);
    if (file_exists($thumbFullPath)) @unlink($thumbFullPath);
    fail("Database error: " . $conn->error);
}

$stmt->bind_param("iiisss", $seriesId, $season, $episode, $description, $videoWebPath, $thumbWebPath);

if (!$stmt->execute()) {
    @unlink($videoFullPath);
    if (file_exists($thumbFullPath)) @unlink($thumbFullPath);
    fail("Database insert failed: " . $conn->error);
}

$videoId = $stmt->insert_id;
$stmt->close();

// Return JSON response for AJAX, redirect for regular form submission
if (is_ajax()) {
    header('Content-Type: application/json');
    echo json_encode([
        "ok" => true,
        "redirect" => base_url("series/" . $seriesId),
        "videoId" => $videoId
    ]);
    exit;
} else {
    redirect("series/" . $seriesId);
}
