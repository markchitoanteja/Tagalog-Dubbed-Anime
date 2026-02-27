<?php
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/auth.php";

$openLogin = (($_GET["login"] ?? "") === "1");
$next = $_GET["next"] ?? "";

$current_url = $_SERVER['REQUEST_URI'];
$is_upload_page = strpos($current_url, '/upload') !== false;
?>
<!doctype html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tagalog Dubbed Anime</title>

    <!-- Adaptive Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= base_url() ?>/favicon-dark.ico" media="(prefers-color-scheme: light)">
    <link rel="icon" type="image/x-icon" href="<?= base_url() ?>/favicon-light.ico" media="(prefers-color-scheme: dark)">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo base_url('assets/style.css'); ?>">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="app-shell">
        <nav class="navbar navbar-expand-lg navbar-clean">
            <div class="container">
                <!-- Brand -->
                <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="index.php">
                    <img src="<?= base_url() ?>/assets/icon-light.png" alt="Logo" width="28" height="28" class="rounded">
                    <span>Tagalog Dubbed Anime</span>
                </a>

                <div class="ms-auto d-flex gap-2 align-items-center">
                    <a class="btn btn-sm btn-outline-light" href="<?php echo base_url(); ?>">
                        <i class="fa-solid fa-book-open me-1"></i>Library
                    </a>

                    <?php if (is_admin()): ?>
                        <?php if (!$is_upload_page): ?>
                            <a class="btn btn-sm btn-accent" href="<?php echo base_url('upload'); ?>">
                                <i class="fa-solid fa-upload me-1"></i>Upload
                            </a>
                        <?php endif; ?>
                        <a class="btn btn-sm btn-outline-light" href="<?php echo base_url('logout'); ?>">
                            <i class="fa-solid fa-right-from-bracket me-1"></i>Logout
                        </a>
                    <?php else: ?>
                        <button class="btn btn-sm btn-accent" type="button" data-bs-toggle="modal" data-bs-target="#adminLoginModal">
                            <i class="fa-solid fa-lock me-1"></i>Admin Login
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <main class="app-main">
            <div class="container py-4">

                <!-- Admin Login Modal -->
                <div class="modal fade" id="adminLoginModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content card-clean">
                            <div class="modal-header border-0">
                                <h5 class="modal-title fw-semibold d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-lock text-secondary"></i> Admin Login
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body pt-0">
                                <div class="text-muted-soft mb-3">Login required to upload, edit, or delete videos.</div>

                                <form id="adminLoginForm">
                                    <input type="hidden" name="next" value="<?php echo h($next); ?>">

                                    <div class="mb-3">
                                        <label class="mb-2 d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-user text-secondary"></i> Username
                                        </label>
                                        <input class="form-control" name="username" autocomplete="username" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="mb-2 d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-key text-secondary"></i> Password
                                        </label>
                                        <input type="password" class="form-control" name="password" autocomplete="current-password" required>
                                    </div>

                                    <button class="btn btn-accent w-100" type="submit">
                                        <i class="fa-solid fa-right-to-bracket me-1"></i> Login
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    $(function() {
                        $("#adminLoginForm").on("submit", function(e) {
                            e.preventDefault();
                            $.ajax({
                                url: "<?php echo base_url('login'); ?>",
                                method: "POST",
                                data: $(this).serialize(),
                                success: function(resp) {
                                    let data = resp;
                                    if (typeof resp === "string") {
                                        try {
                                            data = JSON.parse(resp);
                                        } catch (e) {
                                            data = null;
                                        }
                                    }
                                    if (data && data.ok) {
                                        window.location.href = data.redirect;
                                    } else {
                                        Swal.fire({
                                            icon: "error",
                                            title: "Login failed",
                                            text: (data && data.message) ? data.message : "Unknown error."
                                        });
                                    }
                                },
                                error: function() {
                                    Swal.fire({
                                        icon: "error",
                                        title: "Login failed",
                                        text: "Server error."
                                    });
                                }
                            });
                        });

                        // auto-open modal if redirected with ?login=1
                        const shouldOpen = <?php echo $openLogin ? "true" : "false"; ?>;
                        if (shouldOpen) {
                            const modal = new bootstrap.Modal(document.getElementById("adminLoginModal"));
                            modal.show();
                        }
                    });
                </script>