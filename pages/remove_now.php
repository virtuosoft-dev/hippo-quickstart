<?php require( 'header.php' ); ?>
<?php
    // Validate the job_id
    $job_id = $_GET['job_id'];
    if ( $hcpp->quickstart->is_job_valid( $job_id ) === false ) {
        header( 'Location: ?quickstart=main' );
        exit;
    }

    // Start the remove process
    $hcpp->quickstart->remove_now( $job_id );
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
    <div class="quickstart qs_remove_now">
        <h1>Remove Websites</h1>
        <legend id="status">Please wait. Removing websites and associated resources.</legend>
    </div>
</div>
<script>
    (function($){
        $(function() {

            // Check the import key every 6 seconds
            var remove_int = setInterval( () => {
                $.ajax({
                    url: '../../pluginable.php?load=quickstart&action=remove_result&job_id=<?php echo $job_id; ?>',
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
                            clearInterval( remove_int );
                        }
                    }
                }); 
            }, 6000);
            setTimeout( () => {
                $('.spinner-overlay').addClass('active');
            }, 500);

            // Cancel the removal
            $('#back-button').on('click', (e) => {
                clearInterval( remove_int );
                e.preventDefault();
                $.ajax({
                    url: '../../pluginable.php?load=quickstart&action=cancel_job&job_id=<?php echo $job_id; ?>',
                    type: 'GET',
                    success: function( data ) {
                        $('#error').html( '<p>Remove cancelled. Please click continue.</p>');
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