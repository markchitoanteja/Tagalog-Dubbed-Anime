<?php
require "db.php";
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/auth.php";
if (!is_admin()) require_admin();

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) redirect();

$stmt = $conn->prepare("SELECT series_id, video_path, thumb_path FROM videos WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) redirect();

$seriesId = (int)$row["series_id"];

// Delete row
$stmt = $conn->prepare("DELETE FROM videos WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

// Delete files
$videoRel = $row["video_path"] ?? "";
$thumbRel = $row["thumb_path"] ?? "";

$videoDisk = __DIR__ . "/" . $videoRel;
$thumbDisk = __DIR__ . "/" . $thumbRel;

if (strpos($videoRel, "uploads/videos/") === 0 && file_exists($videoDisk)) unlink($videoDisk);
if (strpos($thumbRel, "uploads/thumbs/") === 0 && file_exists($thumbDisk)) unlink($thumbDisk);

// Remove series if empty
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM videos WHERE series_id=?");
$stmt->bind_param("i", $seriesId);
$stmt->execute();
$c = (int)$stmt->get_result()->fetch_assoc()["c"];
$stmt->close();

if ($c === 0) {
    $stmt = $conn->prepare("DELETE FROM series WHERE id=?");
    $stmt->bind_param("i", $seriesId);
    $stmt->execute();
    $stmt->close();
    redirect();
}

redirect("series/" . $seriesId);
