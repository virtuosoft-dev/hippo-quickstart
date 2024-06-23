<?php
require( 'header.php' ); 
$job_id = $_GET['job_id'];

// Check if job_id is in the session
if ( isset( $_SESSION['devstia_jobs'] ) ) {
    $jobs = $_SESSION['devstia_jobs'];
    if ( !isset( $jobs[$job_id] ) ) {
        $job_id = null;
    }
} else {
    $job_id = null;
}
?>
<div class="toolbar">
    <div class="toolbar-inner">
        <div class="toolbar-buttons">
                <a href="?quickstart=main" class="button button-secondary button-back js-button-back" id="back-button">
                    <i tabindex="300" class="fas fa-stop-circle icon-red"></i>Cancel		
                </a>
            </div>
        <div class="toolbar-buttons">
            <a href="?quickstart=connect_now" class="button" id="connect-button" onclick="document.getElementById('connect-form').submit(); return false;">
                <i tabindex="300" class="fas fa-arrow-right icon-blue"></i>Connect
            </a>         
        </div>
    </div>
</div>
<div class="body-reset container">
    <div class="quickstart qs_connect">
        <h1>Finish Devstia.com Connection</h1>
        <?php if ( !$job_id ) { ?>
            <legend>Invalid job ID. Enter valid credentials to connect this server to your Devstia.com account.</legend>
            <script>
                (function($) {
                    $(function() {
                        // Set focus to connect_username field
                        $('#connect_username').focus();
                    });
                })(jQuery);
            </script>
        <?php } else { ?>
            <legend>The credentials below will be used to connect to your Devstia.com account.</legend>
            <?php
                $connect_username = $_GET['user_login'];
                $connect_password = $_GET['password'];
            ?>
        <?php } ?>
        <form id="connect-form" action="?quickstart=connect_now" method="post">
            <div class="u-mb10">
				<label for="connect_username" class="form-label">Devstia.com Username</label>
				<input tabindex="100" type="text" class="form-control" 
                    name="connect_username" id="connect_username" value="<?php echo $connect_username;?>" required>
			</div>
            <div class="u-mb10">
				<label for="connect_password" class="form-label">Devstia.com Application Password</label>
				<input tabindex="200" type="text" class="form-control" 
                    name="connect_password" id="connect_password" value="<?php echo $connect_password;?>" required>
			</div>
            <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
        </form>
        <p>Please press the connect button to save and finish the connection.</p>
    </div>
</div>


