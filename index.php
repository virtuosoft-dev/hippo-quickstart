<?php

// Continue session from admin login
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// User and action required
if (false == isset($_SESSION['user'])) return;
if (false == isset($_GET['action'])) return;

// Process export actions
if (in_array( $_GET['action'], ['export_status', 'export_cancel', 'download'] )) {

    // Validate export_key
    if (false == (isset($_GET['export_key']) && isset($_SESSION['export_key']))) return;
    if ($_GET['export_key'] != $_SESSION['export_key']) return;
    $export_key = $_GET['export_key'];
    if (false == isset($_SESSION[$export_key . '_pid'])) return;
    $export_id = $_SESSION[$export_key . '_pid'];

    // Check export_pid status
    if ($_GET['action'] == 'export_status') {
        echo shell_exec('/usr/local/hestia/bin/v-invoke-plugin quickstart_export_status ' . $export_id);
    }

    // Cancel the export by killing the process
    if ( $_GET['action'] == 'export_cancel' ) {
        echo shell_exec('/usr/local/hestia/bin/v-invoke-plugin quickstart_export_cancel ' . $export_id);
        unset($_SESSION['export_key']);
        unset($_SESSION[$export_key . '_pid']);
    }

    // Process file download
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
}

// Process file upload
if (in_array( $_GET['action'], ['import_cancel', 'import_status', 'import_result', 'upload'] ) ) {

    // Process file upload
    if ( $_GET['action'] == 'upload' ) {

        // Validate import_key required to process uploads
        if (false == (isset($_GET['import_key']) && isset($_SESSION['import_key']))) return;
        if ($_GET['import_key'] != $_SESSION['import_key']) return;
        if (false == isset($_SESSION['import_key'])) return;
        $import_key = $_SESSION['import_key'];

        // Generate response
        $response = [
            'status' => 'error',
            'message' => 'Unknown error occurred.'
        ];
        $allowedMimeTypes = [
            'application/zip', 
            'application/x-xz', 
            'application/octet-stream', 
            'application/gzip', 
            'application/x-rar-compressed',
            'application/x-tar',
            'application/x-bzip2',
            'application/x-7z-compressed'
        ];
        if ($_FILES['file']['error'] == UPLOAD_ERR_OK && in_array($_FILES['file']['type'], $allowedMimeTypes)) {
            $tmp_name = $_FILES['file']['tmp_name'];
            global $hcpp;
            $ext = $hcpp->delLeftMost( $_FILES['file']['name'], '_' );
            $ext = $hcpp->delLeftMost( $ext, '.' );
            $name = "/tmp/devstia_import_" . $_SESSION['import_key'] . '.' . $ext;
            move_uploaded_file($tmp_name, $name);
            $_SESSION[$import_key . '_file'] = $name;
            $response['status'] = 'uploaded';
            $response['message'] = 'File uploaded. Please click continue.';
        }
        echo json_encode( $response );
    }
 
    // Validate import_pid, $_SESSION['import_key'] not required (multiple imports can be running at once)
    if (false == isset($_GET['import_key'])) return;
    $import_key = $_GET['import_key'];
    if (false == isset($_SESSION[$import_key . '_pid'])) return;
    $import_pid = $_SESSION[$import_key . '_pid'];

    // Check import_pid status
    global $hcpp;
    
    if ( $_GET['action'] == 'import_status' ) {
        echo shell_exec( '/usr/local/hestia/bin/v-invoke-plugin quickstart_import_status ' . $import_pid . ' ' . $import_key);
    }

    // Check import_result
    if ( $_GET['action'] == 'import_result' ) {
        echo shell_exec( '/usr/local/hestia/bin/v-invoke-plugin quickstart_import_result ' . $import_pid . ' ' . $import_key);
    }

    // Cancel the import by killing the process
    if ( $_GET['action'] == 'import_cancel' ) {
        echo shell_exec('/usr/local/hestia/bin/v-invoke-plugin quickstart_import_cancel ' . $import_pid . ' ' . $import_key);
        unset($_SESSION[$import_key]);
    }
}
