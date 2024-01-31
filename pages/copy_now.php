<?php require( 'header.php' ); ?>
<?php

    // Validate the job_id
    $job_id = $_GET['job_id'];
    if ( $hcpp->quickstart->is_job_valid( $job_id ) === false ) {
        header( 'Location: ?quickstart=main' );
        exit;
    }

    // Start the copy process
    $hcpp->quickstart->copy_now( $job_id );


    // // Validate remove_copy key
    // global $hcpp;
    // if (false == (isset($_GET['rc_key']) && isset($_SESSION['rc_key']))) return;
    // if ($_GET['rc_key'] != $_SESSION['rc_key']) return;
    // $rc_key = $_GET['rc_key'];

    // // Gather details and write unique json temp file
    // $site_details = $_SESSION[$rc_key . '_site_details'];
    // $selected_databases = $_REQUEST['selected_databases'];
    // $copy_details = [
    //     'orig_domain' => '',
    //     'new_domain' => '',
    //     'orig_aliases' => [],
    //     'new_aliases' => [],
    //     'databases' => [],
    //     'ref_files' => [],
    //     'user' => ''
    // ];
    // foreach( $site_details as $detail ) {
    //     if ( isset( $detail['DATABASE']) ) {
    //         $database = $detail['DATABASE'];
    //         if ( in_array( $database, $selected_databases ) ) {
    //             $detail['new_DATABASE'] = $_SESSION['user'] . '_' . $hcpp->nodeapp->random_chars(5);
    //             $detail['new_DBPASSWORD'] = $hcpp->nodeapp->random_chars(20);
    //             $copy_details['databases'][] = $detail;
    //         }
    //     }
    //     if ( isset( $detail['domain'] ) ) {
    //         $copy_details['orig_domain'] = $detail['domain'];
    //         $copy_details['orig_aliases'] = $detail['aliases'];
    //         $copy_details['ref_files'] = $detail['ref_files'];
    //         $copy_details['user'] = $_SESSION['user'];
    //     }
    // } 
    // $copy_details['new_domain'] = $_REQUEST['v_domain'];
    // $new_aliases = explode( "\n", $_REQUEST['v_aliases'] );
    // $copy_details['new_aliases'] = array_map( 'trim', $new_aliases );
    // file_put_contents( '/tmp/devstia_copy_' . $rc_key . '.json', json_encode($copy_details, JSON_PRETTY_PRINT) );

    // // Start copy process asynchonously and get the process id
    // $copy_pid = trim( shell_exec(HESTIA_CMD . "v-invoke-plugin quickstart_copy_now " . $rc_key . " > /dev/null 2>/dev/null & echo $!") );

    // // Write the pid to a unique pid temp file
    // file_put_contents( '/tmp/devstia_copy_' . $rc_key . '.pid', $copy_pid );
?>
<div class="toolbar" style="z-index:100;position:relative;">
    <div class="toolbar-inner">
        <div class="toolbar-buttons">
            <a href="#" class="button button-secondary button-back js-button-back" id="back-button">
                <i class="fas fa-stop-circle icon-red"></i>Cancel			
            </a>
        </div>
        <div class="toolbar-buttons">
            <a href="#" class="button disabled" id="continue-button">
                <i class="fas fa-arrow-right icon-blue"></i>Continue
            </a>         
        </div>
    </div>
</div>
<div class="body-reset container">
    <div class="quickstart qs_import_now">
        <h1>Import Website Files</h1>
        <legend id="status">Please wait. Importing website.</legend>
        <div id="options">
            <pre>
                <?php
                    
                ?>
            </pre>
        </div>
    </div>
</div>
<script>
    (function($){
        $(function() {
            // Check the copy key every 8 seconds
            var copy_int = setInterval( () => {
                $.ajax({
                    url: '../../pluginable.php?load=quickstart&action=copy_result&job_id=<?php echo $job_id; ?>',
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
                            $('#options').hide();
                            $('#continue-button').removeClass('disabled');
                            $('.spinner-overlay').removeClass('active');
                            clearInterval( copy_int );
                        }
                    }
                }); 
            }, 6000);
            setTimeout( () => {
                $('.spinner-overlay').addClass('active');
            }, 1000);

            // Cancel the copy
            $('#back-button').on('click', (e) => {
                clearInterval( copy_int );
                e.preventDefault();
                $.ajax({
                    url: '../../pluginable.php?load=quickstart&action=cancel_job&job_id=<?php echo $job_id; ?>',
                    type: 'GET',
                    success: function( data ) {
                        $('#error').html( '<p>Copy cancelled. Please click continue.</p>');
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