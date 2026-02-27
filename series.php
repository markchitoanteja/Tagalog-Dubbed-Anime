<?php
require "db.php";
require_once __DIR__ . "/config.php";
include "header.php";

$seriesId = (int)($_GET["id"] ?? 0);

// pagination
$perPage = 8;
$page = max(1, (int)($_GET["page"] ?? 1));
$offset = ($page - 1) * $perPage;

// fetch series
$stmt = $conn->prepare("SELECT * FROM series WHERE id = ?");
$stmt->bind_param("i", $seriesId);
$stmt->execute();
$series = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$series) {
    echo '<div class="card-clean p-5 text-center">
        <i class="fa-solid fa-triangle-exclamation fa-3x mb-3 text-secondary"></i>
        <h5 class="fw-semibold mb-2">Series not found</h5>
        <a class="btn btn-outline-light btn-sm" href="' . base_url() . '"><i class="fa-solid fa-arrow-left me-1"></i>Back</a>
    </div>';
    include "footer.php";
    exit;
}

// total count
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM videos WHERE series_id = ?");
$stmt->bind_param("i", $seriesId);
$stmt->execute();
$total = (int)($stmt->get_result()->fetch_assoc()["total"] ?? 0);
$stmt->close();

$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

// fetch paginated videos
$stmt = $conn->prepare("
    SELECT *
    FROM videos
    WHERE series_id = ?
    ORDER BY season ASC, episode ASC, uploaded_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $seriesId, $perPage, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-3">
    <div>
        <h3 class="fw-semibold mb-1">
            <i class="fa-solid fa-folder-open me-2 text-secondary"></i><?php echo h($series["title"]); ?>
        </h3>
        <div class="text-muted-soft">
            Episodes list (sorted by Season, Episode). Page <?php echo (int)$page; ?> of <?php echo (int)$totalPages; ?>.
        </div>
    </div>

    <div class="d-flex gap-2">
        <a class="btn btn-outline-light" href="<?php echo base_url(); ?>">
            <i class="fa-solid fa-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<?php if ($result && $result->num_rows > 0): ?>
    <div class="row g-4">
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="col-md-6 col-lg-3">
                <div class="card-clean h-100">
                    <img class="thumb" src="<?php echo base_url(h($row["thumb_path"])); ?>" alt="thumb">

                    <div class="p-3">
                        <div class="fw-semibold">
                            <i class="fa-solid fa-layer-group me-1 text-secondary"></i>
                            Season <?php echo (int)$row["season"]; ?>
                            <span class="text-muted-soft">•</span>
                            <i class="fa-solid fa-list-ol me-1 text-secondary"></i>
                            Episode <?php echo (int)$row["episode"]; ?>
                        </div>

                        <div class="text-muted-soft small mt-2">
                            <?php echo h(mb_strimwidth($row["description"] ?? "", 0, 90, "...")); ?>
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <?php if (is_admin()): ?>
                                <a class="btn btn-sm btn-primary" href="<?php echo base_url('watch/' . (int)$row["id"]); ?>">
                                    <i class="fa-solid fa-play me-1"></i>Watch
                                </a>
                                <a class="btn btn-sm btn-outline-light" href="<?php echo base_url('edit/' . (int)$row["id"]); ?>">
                                    <i class="fa-solid fa-pen-to-square me-1"></i>Edit
                                </a>
                                <button class="btn btn-sm btn-outline-danger ms-auto btn-delete" data-id="<?php echo (int)$row["id"]; ?>">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            <?php else: ?>
                                <a class="btn btn-sm btn-primary w-100" href="<?php echo base_url('watch/' . (int)$row["id"]); ?>">
                                    <i class="fa-solid fa-play me-1"></i>Watch
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="mt-4" aria-label="Episodes pagination">
            <ul class="pagination justify-content-center">

                <?php
                // If your page is NOT series.php, change this line to match your route.
                // Example: base_url('series/' . $seriesId) and then append ?page=...
                $base = base_url('series.php') . '?id=' . $seriesId;

                $prev = max(1, $page - 1);
                $next = min($totalPages, $page + 1);

                $window = 2; // current +/- 2
                $start = max(1, $page - $window);
                $end   = min($totalPages, $page + $window);
                ?>

                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo $base . '&page=' . $prev; ?>" aria-label="Previous">
                        &laquo;
                    </a>
                </li>

                <?php if ($start > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo $base . '&page=1'; ?>">1</a>
                    </li>
                    <?php if ($start > 2): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($p = $start; $p <= $end; $p++): ?>
                    <li class="page-item <?php echo ($p === $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo $base . '&page=' . $p; ?>">
                            <?php echo (int)$p; ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php if ($end < $totalPages): ?>
                    <?php if ($end < $totalPages - 1): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo $base . '&page=' . $totalPages; ?>">
                            <?php echo (int)$totalPages; ?>
                        </a>
                    </li>
                <?php endif; ?>

                <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo $base . '&page=' . $next; ?>" aria-label="Next">
                        &raquo;
                    </a>
                </li>

            </ul>
        </nav>
    <?php endif; ?>

<?php else: ?>
    <div class="card-clean p-5 text-center">
        <i class="fa-solid fa-box-open fa-3x mb-3 text-secondary"></i>
        <h5 class="fw-semibold mb-2">No episodes yet</h5>
        <div class="text-muted-soft mb-3">Upload the first episode for this series.</div>
        <a class="btn btn-accent btn-sm" href="<?php echo base_url('upload'); ?>">
            <i class="fa-solid fa-upload me-1"></i>Upload Episode
        </a>
    </div>
<?php endif; ?>

<script>
    $(document).on("click", ".btn-delete", function() {
        const id = $(this).data("id");
        Swal.fire({
            title: "Delete this episode?",
            text: "This will delete the video file and thumbnail too.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Delete",
            confirmButtonColor: "#dc3545"
        }).then((res) => {
            if (res.isConfirmed) {
                window.location = "<?php echo base_url('delete'); ?>/" + encodeURIComponent(id);
            }
        });
    });
</script>

<?php
$stmt->close();
include "footer.php";
