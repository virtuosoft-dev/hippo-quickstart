<?php require( 'header.php' ); ?>
<?php
    // Validate the job_id
    $job_id = $_GET['job_id'];
    if ( $hcpp->quickstart->is_job_valid( $job_id ) === false ) {
        header( 'Location: ?quickstart=main' );
        exit;
    }

    // Start the create process
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
    <div class="quickstart qs_create_now">
        <h1>Create Website Files</h1>
        <legend id="status">Please wait. Creating website.</legend>
    </div>
</div>
<script>
    (function($){
        $(function() {

            // Check the create key every 6 seconds
            var create_int = setInterval( () => {
                $.ajax({
                    url: '../../pluginable.php?load=quickstart&action=create_result&job_id=<?php echo $job_id; ?>',
                    type: 'GET',
                    success: function( data ) {
                        console.log(data);
                        try {
                            data = JSON.parse( data );
                        } catch( e ) {
                            data = { 'status': 'error', 'message': 'Error parsing JSON: ' + e + "\n" + data };
                        }
                        $('#status').html(data.message);
                        if ( data.status != 'running' ) {
                            $('#back-button').hide();
                            $('#continue-button').removeClass('disabled');
                            $('.spinner-overlay').removeClass('active');
                            clearInterval( create_int );
                        }
                    }
                }); 
            }, 6000);
            setTimeout( () => {
                $('.spinner-overlay').addClass('active');
            }, 1000);

            // Cancel the import
            $('#back-button').on('click', (e) => {
                clearInterval( create_int );
                e.preventDefault();
                $.ajax({
                    url: '../../pluginable.php?load=quickstart&action=cancel_job&job_id=<?php echo $job_id; ?>',
                    type: 'GET',
                    success: function( data ) {
                        $('#error').html( '<p>Create cancelled. Please click continue.</p>');
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