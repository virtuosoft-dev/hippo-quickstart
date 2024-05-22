<?php require( 'header.php' ); ?>
<?php
    // Validate the job_id
    $job_id = $_GET['job_id'];
    if ( $hcpp->quickstart->is_job_valid( $job_id ) === false ) {
        header( 'Location: ?quickstart=main' );
        exit;
    }

    // Get the manifest
    $manifest = $hcpp->quickstart->get_job_data( $job_id, 'manifest' );
    if ( $manifest === false ) {
        header( 'Location: ?quickstart=main' );
        exit;
    }
    $manifest['user'] = $_SESSION['user'];
    $manifest['job_id'] = $job_id;
    $manifest['zip_file'] = $manifest['domain'] . '_' . date( 'Y-m-d-His' ) . '.zip';
    $manifest['export_options'] = $_POST['export_options'] ?? '';
    $manifest['export_adv_options'] = json_decode( $_POST['export_adv_options'] ?? '');
    
    // Run the export process
    $hcpp->quickstart->export_zip( $manifest );
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
                <i tabindex="200" class="fas fa-flag-checkered icon-blue"></i>Finished
            </a>         
        </div>
    </div>
</div>
<div class="body-reset container">
    <div class="quickstart qs_export_now">
        <h1>Export <?php echo $manifest['domain']; ?></h1>
        <legend id="status">Please wait. Copying and compressing files.</legend>
        <div id="error" style="display: none;"></div>
    </div>
</div>
<script>
    (function($){
        $(function() {

            // Check the job_id every 6 seconds
            var export_int = setInterval( () => {
                $.ajax({
                    url: '../../pluginable.php?load=quickstart&action=export_status&job_id=<?php echo $job_id; ?>',
                    type: 'GET',
                    success: function( data ) {
                        console.log( data );
                        try {
                            data = JSON.parse( data );
                        } catch( e ) {
                            $('#error').html( '<p>Error parsing JSON: ' + e + '</p>');
                            $('#error').show();
                        }
                        if ( data.status == 'running' ) return;
                        if ( data.status == 'finished' ) {
                            $('#status').html(`<p>Finished! You can download the exported archive at:</p>
                            <div style="padding:10px;">
                                <strong><a href="../../pluginable.php?load=quickstart&action=download&file=<?php echo $manifest['zip_file']; ?>">
                                <?php
                                    echo $manifest['zip_file'];
                                ?>
                                </a></strong>
                            </div>
                            <br>
                            <?php 
                                if ( $_SESSION['user'] == 'devstia' ) {
                                    echo "<p><strong>Devstia Preview:</strong></p>";
                                    echo '<p>You can also find the file in your <a href="https://devstia.com/docs/devstia-drive" target="_blank">Devstia drive\'s "exports" folder.</p>';
                                }
                            ?>`);                            
                        } else {
                            $('#status').html( data.message || 'An unknown error occurred. Please try again.');
                        }
                        $('#back-button').hide();
                        $('#continue-button').removeClass('disabled');
                        $('#continue-button').attr('href', '?quickstart=main');
                        $('.spinner-overlay').removeClass('active');
                        clearInterval( export_int );
                    }
                });
            }, 6000);
            setTimeout( () => {
                $('.spinner-overlay').addClass('active');
            }, 500);

            // Cancel the export
            $('#back-button').on('click', (e) => {
                clearInterval( export_int );
                e.preventDefault();
                $.ajax({
                    url: '../../pluginable.php?load=quickstart&action=cancel_job&job_id=<?php echo $job_id; ?>',
                    type: 'GET',
                    success: function( data ) {
                        $('#error').html( '<p>Export cancelled.</p>');
                        $('#error').show();
                        $('#back-button').hide();
                        $('#continue-button').removeClass('disabled');
                        $('#continue-button').attr('href', '?quickstart=main');
                        $('.spinner-overlay').removeClass('active');
                    }
                });
            });
        });
    })(jQuery);
</script>