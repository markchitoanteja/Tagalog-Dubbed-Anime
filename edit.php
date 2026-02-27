<?php
require "db.php";
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/auth.php";
if (!is_admin()) require_admin();

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
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    echo '<div class="card-clean p-5 text-center">
    <i class="fa-solid fa-triangle-exclamation fa-3x mb-3 text-secondary"></i>
    <h5 class="fw-semibold mb-2">Episode not found</h5>
    <a class="btn btn-outline-light btn-sm" href="' . base_url() . '"><i class="fa-solid fa-arrow-left me-1"></i>Back</a>
  </div>';
    include "footer.php";
    exit;
}
?>

<h3 class="fw-semibold mb-4 d-flex align-items-center gap-2">
    <i class="fa-solid fa-pen-to-square text-secondary"></i>
    Edit Episode
</h3>

<div class="card-clean p-4">
    <form action="<?php echo base_url('update'); ?>" method="POST">
        <input type="hidden" name="id" value="<?php echo (int)$data["id"]; ?>">

        <div class="mb-3">
            <label class="d-flex align-items-center gap-2 mb-2">
                <i class="fa-solid fa-clapperboard text-secondary"></i>Series Title *
            </label>
            <input type="text" name="series_title" class="form-control" required value="<?php echo h($data["series_title"]); ?>">
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="d-flex align-items-center gap-2 mb-2">
                    <i class="fa-solid fa-layer-group text-secondary"></i>Season *
                </label>
                <input type="number" name="season" class="form-control" required min="1" value="<?php echo (int)$data["season"]; ?>">
            </div>

            <div class="col-md-6 mb-3">
                <label class="d-flex align-items-center gap-2 mb-2">
                    <i class="fa-solid fa-list-ol text-secondary"></i>Episode *
                </label>
                <input type="number" name="episode" class="form-control" required min="1" value="<?php echo (int)$data["episode"]; ?>">
            </div>
        </div>

        <div class="mb-4">
            <label class="d-flex align-items-center gap-2 mb-2">
                <i class="fa-solid fa-align-left text-secondary"></i>Description
            </label>
            <textarea name="description" class="form-control" rows="4"><?php echo h($data["description"] ?? ""); ?></textarea>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-accent">
                <i class="fa-solid fa-floppy-disk me-1"></i>Save
            </button>
            <a class="btn btn-outline-light" href="<?php echo base_url('series/' . (int)$data["series_id"]); ?>">
                <i class="fa-solid fa-arrow-left me-1"></i>Cancel
            </a>
        </div>
    </form>
</div>

<?php include "footer.php"; ?>