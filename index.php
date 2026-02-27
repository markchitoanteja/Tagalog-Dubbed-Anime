<?php
require "db.php";
require_once __DIR__ . "/config.php";
include "header.php";

$q = trim($_GET["q"] ?? "");

$sql = "
  SELECT s.id, s.title,
         COUNT(v.id) AS total_eps,
         MAX(v.uploaded_at) AS last_upload
  FROM series s
  LEFT JOIN videos v ON v.series_id = s.id
";

$params = [];
$types = "";
$where = "";

if ($q !== "") {
    $where = " WHERE s.title LIKE ? ";
    $params[] = "%$q%";
    $types .= "s";
}

$sql .= $where . " GROUP BY s.id ORDER BY s.title ASC";
$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-3">
    <div>
        <h3 class="fw-semibold mb-1">
            <i class="fa-solid fa-clapperboard me-2 text-secondary"></i>Series Library
        </h3>
        <div class="text-muted-soft">Browse anime by series title.</div>
    </div>

    <form class="d-flex gap-2" method="GET" action="<?php echo base_url(); ?>" style="min-width:280px;">
        <div class="input-group">
            <span class="input-group-text bg-dark border-0">
                <i class="fa-solid fa-magnifying-glass text-secondary"></i>
            </span>
            <input class="form-control" name="q" value="<?php echo h($q); ?>" placeholder="Search series title..." autocomplete="off">
        </div>
        <?php if ($q !== ""): ?>
            <a class="btn btn-outline-light" href="<?php echo base_url(); ?>" title="Clear">
                <i class="fa-solid fa-xmark"></i>
            </a>
        <?php endif; ?>
    </form>
</div>

<?php if ($result && $result->num_rows > 0): ?>
    <div class="row g-4">
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="col-md-6 col-lg-3">
                <div class="card-clean p-4 h-100">
                    <div class="fw-semibold fs-5 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-folder text-secondary"></i>
                        <?php echo h($row["title"]); ?>
                    </div>

                    <div class="text-muted-soft small mt-2">
                        <i class="fa-solid fa-list-ul me-1"></i>
                        <?php echo (int)$row["total_eps"]; ?> episode(s)
                    </div>

                    <div class="text-muted-soft small mt-1">
                        <i class="fa-regular fa-clock me-1"></i>
                        <?php echo $row["last_upload"] ? "Updated: " . h($row["last_upload"]) : "No uploads yet"; ?>
                    </div>

                    <div class="mt-3">
                        <a class="btn btn-sm btn-accent" href="<?php echo base_url('series/' . (int)$row["id"]); ?>">
                            <i class="fa-solid fa-folder-open me-1"></i>View Episodes
                        </a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="card-clean p-5 text-center">
        <i class="fa-solid fa-box-open fa-3x mb-3 text-secondary"></i>
        <?php if ($q !== ""): ?>
            <h5 class="fw-semibold mb-2">No series found</h5>
            <div class="text-muted-soft mb-3">No series match "<strong><?php echo h($q); ?></strong>".</div>
            <a class="btn btn-outline-light btn-sm me-2" href="<?php echo base_url(); ?>">
                <i class="fa-solid fa-rotate-left me-1"></i>Clear
            </a>
            <a class="btn btn-accent btn-sm" href="<?php echo base_url('upload'); ?>">
                <i class="fa-solid fa-upload me-1"></i>Upload
            </a>
        <?php else: ?>
            <h5 class="fw-semibold mb-2">No data yet</h5>
            <div class="text-muted-soft mb-3">Upload your first episode to create your first series.</div>
            <a class="btn btn-accent btn-sm" href="<?php echo base_url('upload'); ?>">
                <i class="fa-solid fa-upload me-1"></i>Upload First Episode
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php
$stmt->close();
include "footer.php";
