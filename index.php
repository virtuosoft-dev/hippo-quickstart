<?php

// Continue session from admin login
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// User and action required
if (false == isset($_SESSION['user'])) return;
if (false == isset($_GET['action'])) return;

global $hcpp;

if ( $_GET['action'] == 'proxy' ) {

    function getDevstia($url, $postData = null) {
        // Only allow devstia.com access
        if (substr($url, 0, 19) !== 'https://devstia.com') {
            return [
                'response' => 'Access denied',
                'headers' => [
                    'http_code' => 403
                ]
            ];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
        // Support cookies
        $user = $_SESSION['user'];
        curl_setopt($ch, CURLOPT_COOKIEJAR, "/tmp/devstia_$user-cookies.dat");
        curl_setopt($ch, CURLOPT_COOKIEFILE, "/tmp/devstia_$user-cookies.dat");
    
        // Support POST data
        if ($postData !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }
    
        $response = curl_exec($ch);
        $responseHeaders = curl_getinfo($ch);
        curl_close($ch);
        return [
            'response' => $response,
            'headers' => $responseHeaders
        ];
    }

    if ( !isset( $_GET['url'] ) ) {
        $url = "https://devstia.com/";
    }else{
        $url = $_GET['url'];
    }
    $postData = !empty($_POST) ? $_POST : null;
    $reply = getDevstia( $url, $postData );

    // Support single redirects
    if ( $reply['headers']['redirect_url'] != '') {
        $url = $reply['headers']['redirect_url'];
        $reply = getDevstia( $url, $postData );
    }
    $response = $reply['response'];
    $responseHeaders = $reply['headers'];

    // Rewrite all URLs to use the proxy
    global $hcpp;
    $new_response = '';
    while( strpos( $response, '"https://devstia.com' ) !== false ) {
        $new_response .= $hcpp->getLeftMost( $response, '"https://devstia.com' );
        $response = $hcpp->delLeftMost( $response, '"https://devstia.com' );
        $remaining_url = $hcpp->getLeftMost( $response, '"' ) . '"';
        $old_url = '"https://devstia.com' . $remaining_url;
        $new_url = '"https://local.dev.pw:8083/pluginable.php?load=quickstart&action=proxy&url=https://devstia.com' . $remaining_url;
        $response = $hcpp->delLeftMost( $response, '"' );

        // Don't bother proxying images, css, js
        $image_ext = ['png', 'jpg', 'jpeg', 'gif', 'svg', 'css', 'js', 'zip'];
        $ext = substr( $hcpp->getRightMost( $old_url, '.' ), 0, 3 );
        if ( in_array( $ext, $image_ext ) ) {
            $new_response .= $old_url;
        }else{
            $new_response .= $new_url;
        }
    }
    if ( $new_response != '') {
        $new_response .= $response;
        $response = $new_response;
    }
    
    // Forward the response headers to the client   
    foreach ($responseHeaders as $headerName => $headerValue) {
        $headerName = str_replace('_', '-', $headerName);
        if (is_array($headerValue)) {
            foreach ($headerValue as $singleHeaderValue) {
                header("$headerName: $singleHeaderValue", false);
            }
        } else {
            header("$headerName: $headerValue");
        }
    }
    $response = $hcpp->do_action( 'quickstart_proxy_response', $response );

    // Inject our remote.css script into header
    $response = str_replace( 
        '</head>', 
        '<link rel="stylesheet" type="text/css" href="https://local.dev.pw:8083/pluginable.php?load=quickstart&action=remote_css"></head>'
        , $response
    );

    // Inject our remote.js script into body
    $response = str_replace( 
        '</body>', 
        '<script src="https://local.dev.pw:8083/pluginable.php?load=quickstart&action=remote_js"></script></body>'
        , $response
    );
    echo $response;
    exit;
}

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
if ( $_GET['action'] == 'blueprint' )  {
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

// Get blueprint download status
if ( $_GET['action'] == 'blueprint_status' ) {
    $status = $hcpp->quickstart->get_status( $job_id );

    // Download blueprint status has finished, return manifest
    if ( $status['status'] === 'finished' ) {
        $user = $hcpp->quickstart->get_job_data( $job_id, 'user' );
        $url = $hcpp->quickstart->get_job_data( $job_id, 'url' );
        $folder = basename( $url );
        if ( substr( $folder, -4 ) == '.zip' ) {
            $folder = substr( $folder, 0, -4 );
        }
        $folder = "/home/$user/web/blueprints/$folder";
        $manifest = "$folder/devstia_manifest.json";

        // Check if the blueprint folder and manifest exists
        if ( is_dir( $folder ) && file_exists( $manifest ) ) {
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
            $status['message'] = 'Blueprint error. Please try again.';   
        }
    }
    echo json_encode( $status );
}

// Clean up the job by job id
if ( $_GET['action'] == 'cleanup_job' ) {
    $hcpp->quickstart->cleanup_job_data( $job_id );
}

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

// Get detail status
if ( $_GET['action'] == 'detail_status' ) {
    $status = $hcpp->quickstart->get_status( $job_id );

    // Detail status has finished, return all the manifests
    echo json_encode( $status );
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

// Check import_result, copy_result, remove_result
if ( $_GET['action'] == 'import_result' ||
     $_GET['action'] == 'copy_result' || 
     $_GET['action'] == 'remove_result') {
    $status = $hcpp->quickstart->get_status( $job_id );
    echo json_encode( $status );
}

 
