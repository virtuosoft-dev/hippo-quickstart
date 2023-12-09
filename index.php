<?php

// Continue session from admin login
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Validate export key, user session
if (false == (isset($_GET['export_key']) && isset($_SESSION['export_key']))) return;
if ($_GET['export_key'] != $_SESSION['export_key']) return;
if (false == isset($_SESSION['export_pid'])) return;
if (false == isset($_SESSION['user'])) return;
if (false == isset($_GET['action'])) return;

// Check export_pid status
if ($_GET['action'] == 'export_status') {
    echo shell_exec('/usr/local/hestia/bin/v-invoke-plugin quickstart_export_status ' . $_SESSION['export_pid']);
}

// Cancel the export by killing the process
if ( $_GET['action'] == 'cancel' ) {
    echo shell_exec('/usr/local/hestia/bin/v-invoke-plugin quickstart_export_cancel ' . $_SESSION['export_pid']);
}

// Force file download
if ( $_GET['action'] == 'download')  {
    $devstia_manifest = $_SESSION['devstia_manifest'];
    $user = $_SESSION['user'];
    $file = "/home/$user/web/exports/" . $devstia_manifest['zip_file'];

    if ( file_exists( $file ) ) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));

        // Turn off output buffering
        if (ob_get_level()) {
            ob_end_clean();
        }
        $handle = fopen($file, 'rb');
        fpassthru($handle);
        fclose($handle);
        exit;
    }
}
