<?php

// Continue session from admin login
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// User and action required
if (false == isset($_SESSION['user'])) return;
if (false == isset($_GET['action'])) return;

global $hcpp;

// Serve up remote.js script
if ( $_GET['action'] == 'remote_js' ) {
    header('Content-Type: application/javascript');
    echo file_get_contents( __DIR__ . '/remote.js' );
    exit;
}

// Serve up remote.css script
if ( $_GET['action'] == 'remote_css' ) {
    header('Content-Type: text/css');
    echo file_get_contents( __DIR__ . '/remote.css' );
    exit;
}

// Process file download
if ( $_GET['action'] == 'download' )  {
    if ( !isset( $_GET['file'] ) ) return;
    
    // Sanitize the file path
    $file = $_GET['file'];
    $file = str_replace( '/', '', $file );
    $file = str_replace( '\\', '', $file );
    $file = str_replace( '..', '', $file );
    $file = "/home/" . $_SESSION['user'] . "/web/exports/" . $file;

    if ( file_exists( $file ) ) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename( $file ) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize( $file ));

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

// Process file delete
if ( $_GET['action'] == 'delete_export' ) {
    if ( !isset( $_GET['file'] ) ) return;

    // Sanitize the file path
    $file = $_GET['file'];
    $file = str_replace( '/', '', $file );
    $file = str_replace( '\\', '', $file );
    $file = str_replace( '..', '', $file );
    $file = "/home/" . $_SESSION['user'] . "/web/exports/" . $file;

    $hcpp->quickstart->delete_export( $file );
    header('Location: list/web/?quickstart=export_view' );
}

// Check job_id
if (false == isset($_GET['job_id'])) return;
$job_id = $_GET['job_id'];
if ( $hcpp->quickstart->is_job_valid( $job_id ) === false ) return;

// Clean up the job by job id
if ( $_GET['action'] == 'cleanup_job' ) {
    $hcpp->quickstart->cleanup_job_data( $job_id );
}

// Cancel the job by job id
if ( $_GET['action'] == 'cancel_job' ) {
    $hcpp->quickstart->cancel_job( $job_id );
}


// Check status and result actions
if ( in_array( $_GET['action'], ['blueprint_status', 'export_status', 'detail_status', 'import_status',
    'import_result', 'copy_result', 'create_result', 'remove_result'] ) ) {
    $status = $hcpp->quickstart->get_status( $job_id );

    // Check for manifests in blueprint and import status
    if ( $status['status'] === 'finished' &&  in_array( $_GET['action'], ['blueprint_status', 'import_status'] ) ) {
        $manifest = "/home/admin/tmp/devstia_$job_id-import/devstia_manifest.json";
        if ( $_GET['action'] == 'blueprint_status' ) {
            $user = $hcpp->quickstart->get_job_data( $job_id, 'user' );
            $url = $hcpp->quickstart->get_job_data( $job_id, 'url' );
            $folder = basename( $url );
            if ( substr( $folder, -4 ) == '.zip' ) {
                $folder = substr( $folder, 0, -4 );
            }
            $folder = "/home/$user/web/blueprints/$folder";
            $manifest = "$folder/devstia_manifest.json";        
        }
        if ( file_exists( $manifest ) ) {
            try {
                $content = file_get_contents( $manifest );
                $manifest = json_decode( $content, true );
            } catch( Exception $e ) {
                echo json_encode( [ 'status' => 'error', 'message' => 'Error parsing manifest file.' ] );

                // Cleanup the job
                $hcpp->quickstart->cleanup_job_data( $job_id );
            }
            $message = 'Fill in options.';
            if ( is_dir('/home/devstia') ) {
                $message .= ' <i>Devstia Personal Web should use a <b>.dev.pw</b> TLD.</i>';
            }
            $status['message'] = $message;
            $status['manifest'] = $manifest;
        }else{
            $status['status'] = 'error';
            $status['message'] = 'Manifest error. Please try again.';

            // Cleanup the job
            $hcpp->quickstart->cleanup_job_data( $job_id );
        }
    }
    echo json_encode( $status );
}
