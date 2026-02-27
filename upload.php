<?php
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/auth.php";
if (!is_admin()) require_admin();
include "header.php";
?>

<h3 class="fw-semibold mb-4 d-flex align-items-center gap-2">
    <i class="fa-solid fa-cloud-arrow-up text-secondary"></i>
    Upload Episode
</h3>

<div class="card-clean p-4">
    <form id="uploadForm" action="<?php echo base_url('save-upload'); ?>" method="POST" enctype="multipart/form-data">

        <div class="mb-3">
            <label class="d-flex align-items-center gap-2 mb-2">
                <i class="fa-solid fa-clapperboard text-secondary"></i>
                Series Title *
            </label>
            <input type="text" name="series_title" class="form-control" required placeholder="e.g. SPY x FAMILY">
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="d-flex align-items-center gap-2 mb-2">
                    <i class="fa-solid fa-layer-group text-secondary"></i>
                    Season *
                </label>
                <input type="number" name="season" class="form-control" required min="1" placeholder="e.g. 1">
            </div>

            <div class="col-md-6 mb-3">
                <label class="d-flex align-items-center gap-2 mb-2">
                    <i class="fa-solid fa-list-ol text-secondary"></i>
                    Episode *
                </label>
                <input type="number" name="episode" class="form-control" required min="1" placeholder="e.g. 1">
            </div>
        </div>

        <div class="mb-3">
            <label class="d-flex align-items-center gap-2 mb-2">
                <i class="fa-solid fa-align-left text-secondary"></i>
                Description
            </label>
            <textarea name="description" class="form-control" rows="4" placeholder="Optional"></textarea>
        </div>

        <div class="mb-4">
            <label class="d-flex align-items-center gap-2 mb-2">
                <i class="fa-solid fa-video text-secondary"></i>
                Video File *
            </label>
            <input id="videoInput" type="file" name="video" class="form-control" required accept="video/*">
            <div class="text-muted-soft small mt-2">
                Uploading will show progress. After upload, FFmpeg will extract the thumbnail.
            </div>
        </div>

        <div class="d-flex gap-2">
            <button id="btnUpload" class="btn btn-accent" type="submit">
                <i class="fa-solid fa-upload me-1"></i>Upload
            </button>
            <a href="<?php echo base_url(); ?>" class="btn btn-outline-light">
                <i class="fa-solid fa-xmark me-1"></i>Cancel
            </a>
        </div>
    </form>
</div>

<script>
    $("#uploadForm").on("submit", function(e) {
        e.preventDefault();

        const form = this;
        const file = $("#videoInput")[0].files[0];
        if (!file) {
            Swal.fire({
                icon: "error",
                title: "No file selected",
                text: "Please choose a video file."
            });
            return;
        }

        // Open SweetAlert with progress bar
        Swal.fire({
            title: "Uploading…",
            html: `
      <div class="text-muted-soft mb-2">Please keep this tab open.</div>
      <div class="progress" style="height:16px;">
        <div id="uploadBar" class="progress-bar" role="progressbar" style="width:0%">0%</div>
      </div>
      <div id="uploadStatus" class="text-muted-soft small mt-2">Starting…</div>
    `,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false
        });

        $("#btnUpload").prop("disabled", true);

        const fd = new FormData(form);

        $.ajax({
            url: $(form).attr("action"),
            type: "POST",
            data: fd,
            processData: false,
            contentType: false,

            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        const percent = Math.round((evt.loaded / evt.total) * 100);
                        $("#uploadBar").css("width", percent + "%").text(percent + "%");
                        $("#uploadStatus").text("Uploading video…");
                    }
                }, false);
                return xhr;
            },

            success: function(resp) {
                // After upload completes, server still needs time for FFmpeg processing
                $("#uploadStatus").text("Processing (extracting thumbnail)…");

                // Expect JSON: {ok:true, redirect:"..."} or {ok:false, message:"..."}
                let data = resp;
                if (typeof resp === "string") {
                    try {
                        data = JSON.parse(resp);
                    } catch (e) {
                        data = null;
                    }
                }

                if (data && data.ok) {
                    Swal.fire({
                        icon: "success",
                        title: "Uploaded!",
                        text: "Redirecting…",
                        timer: 900,
                        showConfirmButton: false,
                        allowOutsideClick: false
                    }).then(() => {
                        window.location.href = data.redirect;
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Upload failed",
                        text: (data && data.message) ? data.message : "Something went wrong. Check ffmpeg_log.txt."
                    });
                    $("#btnUpload").prop("disabled", false);
                }
            },

            error: function(xhr) {
                Swal.fire({
                    icon: "error",
                    title: "Upload failed",
                    text: xhr.responseText ? xhr.responseText : "Server error."
                });
                $("#btnUpload").prop("disabled", false);
            }
        });
    });
</script>

<?php include "footer.php"; ?>