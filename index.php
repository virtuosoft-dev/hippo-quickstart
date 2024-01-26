<?php

// Continue session from admin login
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// User and action required
if (false == isset($_SESSION['user'])) return;
if (false == isset($_GET['action'])) return;
if (false == isset($_GET['job_id'])) return;
$job_id = $_GET['job_id'];

// Check job_id
global $hcpp;
if ( $hcpp->quickstart->is_job_valid( $_GET['job_id'] ) === false ) return;

// Cancel the job by job id
if ( $_GET['action'] == 'cancel_job' ) {
    $hcpp->quickstart->cancel_job( $job_id );
}

// Get export status
if ( $_GET['action'] == 'export_status' ) {
    $status = $hcpp->quickstart->get_status( $job_id );
    if ( $status['status'] === 'finished' ) {
        $hcpp->quickstart->cleanup_job_data( $job_id );
    }
    echo json_encode( $hcpp->quickstart->get_status( $job_id ) );
}

// Process file download
if ( $_GET['action'] == 'download')  {
    $manifest = $hcpp->quickstart->get_job_data( $job_id, 'manifest' );
    $user = $_SESSION['user'];
    $file = "/home/$user/web/exports/" . $manifest['zip_file'];

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

// Process file upload
if ( $_GET['action'] == 'upload' ) {

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
        $ext = $hcpp->delLeftMost( $_FILES['file']['name'], '_' );
        $ext = $hcpp->delLeftMost( $_FILES['file']['name'], '-' );
        $ext = $hcpp->delLeftMost( $ext, '.' );
        $name = "/tmp/devstia_$job_id-import.$ext";
        move_uploaded_file($tmp_name, $name);
        $hcpp->quickstart->set_job_data( $job_id, 'file', $name );
        $response['status'] = 'uploaded';
        $response['message'] = 'File uploaded. Please click continue.';
        $hcpp->quickstart->set_job_data( $job_id, 'import_file', $name );
    }
    echo json_encode( $response );
}

// Get import status
if ( $_GET['action'] == 'import_status' ) {
    $status = $hcpp->quickstart->get_status( $job_id );

    // Import's decompress is finished, return manifest
    if ( $status['status'] === 'finished' ) {
        $manifest = "/tmp/devstia_$job_id-import/devstia_manifest.json";
        if ( file_exists( $manifest ) ) {
            try {
                $content = file_get_contents( $manifest );
                $manifest = json_decode( $content, true );
            } catch( Exception $e ) {
                echo json_encode( [ 'status' => 'error', 'message' => 'Error parsing manifest file.' ] );
            }
            $message = 'Fill in options.';
            if ( is_dir('/home/devstia') ) {
                $message .= ' <i>Devstia Preview should use a <b>.dev.pw</b> TLD.</i>';
            }
            $status['message'] = $message;
            $status['manifest'] = $manifest;
        }else{
            $status['status'] = 'error';
            $status['message'] = 'Import failed. Please try again.';
        }
    }
    echo json_encode( $status );
}

// Check import_result
if ( $_GET['action'] == 'import_result' ) {
    $status = $hcpp->quickstart->get_status( $job_id );
    echo json_encode( $status );
}
 
