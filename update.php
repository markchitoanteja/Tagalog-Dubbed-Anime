<?php
require "db.php";
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/auth.php";
if (!is_admin()) require_admin();

function fail($msg)
{
    die("Update Error: " . h($msg));
}

$id = (int)($_POST["id"] ?? 0);
$seriesTitle = trim($_POST["series_title"] ?? "");
$season = $_POST["season"] ?? "";
$episode = $_POST["episode"] ?? "";
$description = trim($_POST["description"] ?? "");

if ($id <= 0) fail("Invalid episode.");
if ($seriesTitle === "") fail("Series title is required.");
if ($season === "" || !ctype_digit((string)$season) || (int)$season < 1) fail("Season must be >= 1.");
if ($episode === "" || !ctype_digit((string)$episode) || (int)$episode < 1) fail("Episode must be >= 1.");

$season = (int)$season;
$episode = (int)$episode;

// Current row
$stmt = $conn->prepare("SELECT series_id FROM videos WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$current = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$current) fail("Episode not found.");

$oldSeriesId = (int)$current["series_id"];

// Find or create new series
$stmt = $conn->prepare("SELECT id FROM series WHERE title=?");
$stmt->bind_param("s", $seriesTitle);
$stmt->execute();
$found = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($found) {
    $newSeriesId = (int)$found["id"];
} else {
    $stmt = $conn->prepare("INSERT INTO series (title) VALUES (?)");
    $stmt->bind_param("s", $seriesTitle);
    if (!$stmt->execute()) fail("Could not create series.");
    $newSeriesId = (int)$stmt->insert_id;
    $stmt->close();
}

// Prevent duplicates
$stmt = $conn->prepare("SELECT id FROM videos WHERE series_id=? AND season=? AND episode=? AND id<>?");
$stmt->bind_param("iiii", $newSeriesId, $season, $episode, $id);
$stmt->execute();
$dup = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($dup) fail("Another episode already exists with the same Season/Episode in this series.");

// Update
$stmt = $conn->prepare("UPDATE videos SET series_id=?, season=?, episode=?, description=? WHERE id=?");
$stmt->bind_param("iiisi", $newSeriesId, $season, $episode, $description, $id);
$stmt->execute();
$stmt->close();

// Cleanup old series if empty
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM videos WHERE series_id=?");
$stmt->bind_param("i", $oldSeriesId);
$stmt->execute();
$c = (int)$stmt->get_result()->fetch_assoc()["c"];
$stmt->close();

if ($c === 0) {
    $stmt = $conn->prepare("DELETE FROM series WHERE id=?");
    $stmt->bind_param("i", $oldSeriesId);
    $stmt->execute();
    $stmt->close();
}

redirect("series/" . $newSeriesId);
