<?php
// inc/admindashboard-print-studio.php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// This file simply outputs the container that the JS will target.
// Your existing admindashboard.php already contains the same element:
// <div id="custom-print-studio-app"></div>
// Use this include if you prefer modular file structure.

?>
<div id="custom-print-studio-app" class="aakaari-print-studio-root">
    <!-- Initial loading UI (JS will replace this) -->
    <div class="ps-card">
        <h3>Loading Custom Print Studio...</h3>
        <p class="ps-helper">If this message does not change, check that the print-studio assets are installed under <code>/assets/print-studio/</code> and that inc/print-studio-init.php is included from functions.php.</p>
    </div>
</div>
