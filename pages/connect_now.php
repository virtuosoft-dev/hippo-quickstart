<?php
require( 'header.php' ); 
$credentials_saved = false;

// Check for and save form submission
if ( isset( $_POST['connect_username'] ) && isset( $_POST['connect_password'] ) && isset( $_POST['job_id']) ) {
    $connect_username = $_POST['connect_username'];
    $connect_password = $_POST['connect_password'];
    $job_id = $_POST['job_id'];


    // Check if the job_id is in the session and username and password do not contain colon character
    if ( isset( $_SESSION['devstia_jobs'][$job_id] ) && strpos( $connect_username, ':' ) === false && strpos( $connect_password, ':' ) === false ) {

        // Sanitize the connect_username, it should not contain punctuation, spaces, or special characters
        $connect_username = preg_replace( '/[^a-zA-Z0-9]/', '', $connect_username );
        $connect_username = trim( $connect_username );

        // Sanitize the connect_password, it should not contain spaces, or be longer that 32 characters
        $connect_password = preg_replace( '/\s/', '', $connect_password );
        $connect_password = substr( $connect_password, 0, 32 );
        $connect_password = trim( $connect_password );

        // Save the credentials to the file
        $file = "/tmp/devstia_$job_id-devstia-com";
        file_put_contents( $file, $connect_username . ':' . $connect_password );
        global $hcpp;
        $user = $_SESSION['user'];
        $hcpp->run( "invoke-plugin quickstart_connect_save $job_id $user" );
        $credentials_saved = true;

        // Invalidate the job_id session
        unset( $_SESSION['devstia_jobs'][$job_id] );
    }
}
?>
<div class="toolbar">
    <div class="toolbar-inner">
        <div class="toolbar-buttons">
        </div>
        <div class="toolbar-buttons">
            <a href="?quickstart=main" class="button">
                <i tabindex="300" class="fas fa-arrow-right icon-blue"></i>Continue
            </a>         
        </div>
    </div>
</div>
<div class="body-reset container">
    <div class="quickstart qs_connect">
        <?php if ( $credentials_saved === false ): ?>
            <h1>Connection Failed to Save</h1>
            <legend>Unable to save credentials. Please try again.</legend>
        <?php else: ?>
            <h1>Connection to Devstia.com Saved</h1>
            <legend>Your credentials have been saved.</legend>
            <p>Click the continue button to proceed.</p>
        <?php endif; ?>
    </div>
</div>