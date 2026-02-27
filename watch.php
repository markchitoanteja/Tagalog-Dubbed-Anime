<?php
require "db.php";
require_once __DIR__ . "/config.php";
include "header.php";

$id = (int)($_GET["id"] ?? 0);

$stmt = $conn->prepare("
  SELECT v.*, s.title AS series_title
  FROM videos v
  JOIN series s ON s.id = v.series_id
  WHERE v.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$video = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$video) {
    echo '<div class="card-clean p-5 text-center">
    <i class="fa-solid fa-triangle-exclamation fa-3x mb-3 text-secondary"></i>
    <h5 class="fw-semibold mb-2">Episode not found</h5>
    <a class="btn btn-outline-light btn-sm" href="' . base_url() . '"><i class="fa-solid fa-arrow-left me-1"></i>Back</a>
  </div>';
    include "footer.php";
    exit;
}

$seriesTitle = $video["series_title"];
$s = $video["season"];
$e = $video["episode"];
$desc = $video["description"] ?? "";
$videoPath = $video["video_path"];
$thumb = $video["thumb_path"] ?: "assets/placeholder.png";
?>

<div class="d-flex justify-content-between align-items-end mb-3 flex-wrap gap-3">
    <div>
        <h3 class="fw-semibold mb-1 d-flex align-items-center gap-2">
            <i class="fa-solid fa-play text-secondary"></i>
            <?php echo h($seriesTitle); ?>
        </h3>
        <div class="text-muted-soft">
            <i class="fa-solid fa-layer-group me-1"></i>Season <?php echo (int)$s; ?>
            <span class="mx-1">•</span>
            <i class="fa-solid fa-list-ol me-1"></i>Episode <?php echo (int)$e; ?>
        </div>
    </div>

    <div class="d-flex gap-2">
        <a class="btn btn-outline-light" href="<?php echo base_url('series/' . (int)$video["series_id"]); ?>">
            <i class="fa-solid fa-arrow-left me-1"></i>Back
        </a>
        <a class="btn btn-outline-light" href="<?php echo base_url('edit/' . (int)$video["id"]); ?>">
            <i class="fa-solid fa-pen-to-square me-1"></i>Edit
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="video-wrap p-2">
            <video controls class="w-100" poster="<?php echo base_url($thumb); ?>">
                <source src="<?php echo base_url($videoPath); ?>">
                Your browser does not support the video tag.
            </video>
        </div>

        <div class="text-muted-soft small mt-2">
            <i class="fa-solid fa-circle-info me-1"></i>Tip: MP4 (H.264/AAC) works best in browsers.
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card-clean p-4">
            <div class="fw-semibold mb-2 d-flex align-items-center gap-2">
                <i class="fa-solid fa-align-left text-secondary"></i>Description
            </div>
            <div class="text-muted-soft"><?php echo nl2br(h($desc)); ?></div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>