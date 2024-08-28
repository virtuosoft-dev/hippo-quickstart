<?php require( 'header.php' ); ?>
<?php
    // Validate the job_id
    $job_id = $_GET['job_id'];
    if ( $hcpp->quickstart->is_job_valid( $job_id ) === false ) {
        header( 'Location: ?quickstart=main' );
        exit;
    }

    // Start the import process
    $hcpp->quickstart->import_now( $job_id );
?>
<div class="toolbar" style="z-index:100;position:relative;">
    <div class="toolbar-inner">
        <div class="toolbar-buttons">
            <a href="#" class="button button-secondary button-back js-button-back" id="back-button">
                <i tabindex="300" class="fas fa-stop-circle icon-red"></i>Cancel			
            </a>
        </div>
        <div class="toolbar-buttons">
            <a href="#" class="button disabled" id="continue-button">
                <i tabindex="200" class="fas fa-arrow-right icon-blue"></i>Continue
            </a>         
        </div>
    </div>
</div>
<div class="body-reset container">
    <div class="quickstart qs_import_now">
        <h1>Import Website Files</h1>
        <legend id="status">Please wait. Importing website.</legend>
    </div>
</div>
<script>
    (function($){
        $(function() {

            // Check the import key every 6 seconds
            var import_int = setInterval( () => {
                $.ajax({
                    url: '../../pluginable.php?load=quickstart&action=import_result&job_id=<?php echo $job_id; ?>',
                    type: 'GET',
                    success: function( data ) {
                        console.log(data);
                        try {
                            data = JSON.parse( data );
                        } catch( e ) {
                            data = { 'status': 'error', 'message': 'Error parsing JSON: ' + e + "\n" + data };
                        }
                        let msg = data.message.trim();
                        if (msg != '') $('#status').html(msg);
                        if ( data.status != 'running' ) {
                            $('#back-button').hide();
                            $('#continue-button').removeClass('disabled');
                            $('.spinner-overlay').removeClass('active');
                            clearInterval( import_int );
                        }
                    }
                }); 
            }, 6000);
            $('.spinner-overlay').addClass('active');

            // Cancel the import
            $('#back-button').on('click', (e) => {
                clearInterval( import_int );
                e.preventDefault();
                $.ajax({
                    url: '../../pluginable.php?load=quickstart&action=cancel_job&job_id=<?php echo $job_id; ?>',
                    type: 'GET',
                    success: function( data ) {
                        $('#error').html( '<p>Import cancelled. Please click continue.</p>');
                        $('#error').show();
                        $('#back-button').hide();
                        $('#continue-button').removeClass('disabled');
                        $('.spinner-overlay').removeClass('active');
                    }
                });
            });

            // Continue button
            $('#continue-button').on('click', (e) => {
                e.preventDefault();
                $.ajax({
                    url: '../../pluginable.php?load=quickstart&action=cleanup_job&job_id=<?php echo $job_id; ?>',
                    type: 'GET',
                    success: function( data ) {
                        window.location = "?quickstart=main";
                    }
                });
            });
        });
    })(jQuery);
</script>