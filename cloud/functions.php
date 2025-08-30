<?php


function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function showFlashModal() {
    if (!empty($_SESSION['flash'])) {
        $type = $_SESSION['flash']['type'];
        $msg  = $_SESSION['flash']['message'];
        unset($_SESSION['flash']);

        $title = $type === 'success' ? "Success" : "Error";
        $color = $type === 'success' ? "bg-success" : "bg-danger";

        echo <<<HTML
        <div class="modal fade" id="flashMsg" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header $color text-white">
                <h5 class="modal-title">$title</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body"><p>$msg</p></div>
            </div>
          </div>
        </div>
        <script>
        var m = new bootstrap.Modal(document.getElementById('flashMsg'));
        m.show();
        </script>
        HTML;
    }
}
?>
