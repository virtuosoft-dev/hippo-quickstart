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
    // exit();

    // if (false == isset($_SESSION[$job_id . '_file'])) return;
    // $import_file = $_SESSION[$job_id . '_file'];
    // unset ($_SESSION['job_id' . '_file']);

    // // Write request with import file to temp file
    // global $hcpp;
    // $import_folder = $hcpp->getLeftMost( $import_file, '.' );
    // $_REQUEST['import_folder'] = $import_folder;
    // $_REQUEST['user'] = $_SESSION['user'];
    // $request_file = '/tmp/devstia_import_' . $job_id . '.json';
    // file_put_contents( $request_file, json_encode($_REQUEST, JSON_PRETTY_PRINT) );
    
    // // Start import process asynchonously and get the process id
    // $import_pid = trim( shell_exec(HESTIA_CMD . "v-invoke-plugin quickstart_import_now " . $job_id . " > /dev/null 2>/dev/null & echo $!") );
    // $_SESSION[$job_id . '_pid'] = $import_pid;
?>
<div class="toolbar" style="z-index:100;position:relative;">
    <div class="toolbar-inner">
        <div class="toolbar-buttons">
            <a href="#" class="button button-secondary button-back js-button-back" id="back">
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

            // Cancel the import
            $('#back').on('click', (e) => {
                e.preventDefault();
                $.ajax({
                    url: '../../pluginable.php?load=quickstart&action=cancel_job&job_id=<?php echo $job_id; ?>',
                    type: 'GET',
                    success: function( data ) {
                        $('#error').html( '<p>Import cancelled. Please click continue.</p>');
                        $('#error').show();
                        $('#back').hide();
                        $('#continue-button').removeClass('disabled');
                        $('#continue-button').attr('href', '?quickstart=main');
                        $('.spinner-overlay').removeClass('active');
                    }
                });
            });

            // Check the import key every 8 seconds
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
                        $('#status').html(data.message);
                        if ( data.status != 'running' ) {
                            $('#back').hide();
                            $('#continue-button').removeClass('disabled');
                            $('#continue-button').attr('href', '?quickstart=main');
                            $('.spinner-overlay').removeClass('active');
                            clearInterval( import_int );
                        }
                    }
                }); 
            }, 6000);
            //             if ( data.status == 'running' ) return;
            //             if ( data.status == 'finished' ) {
            //                 $('#status').html(data.message);
            //                 let html = `<div class="u-mb10">
            //                                 <label for="v_domain" class="form-label">Import Web Domain</label>
            //                                 <input type="text" class="form-control" name="v_domain" id="v_domain" value="" required="">
            //                             </div>`;
            //                 if (data.alias.trim() != '') {
            //                     const aliases = data.alias.replace(',', "\n");
            //                     html += `<div class="u-mb10">
            //                                 <label for="v_aliases" class="form-label">Aliases</label>
            //                                 <textarea class="form-control" name="v_aliases" id="v_aliases">${aliases}</textarea>
            //                             </div>`;
            //                 }
            //                 $('#options').html(html);
            //                 $('#v_domain').val(data.domain);
            //                 setTimeout(()=>{
            //                     $('#v_domain').focus().select();
            //                 }, 500);
            //             } else {
            //                 if ( data.status == 'error' ) {
            //                     $('#status').html(data.message);
            //                 }else{
            //                     $('#status').html('An unknown error occurred. Please try again.');
            //                 }
            //             }
            //             $('#continue-button').removeClass('disabled');
            //             $('#continue-button').attr('href', '?quickstart=import_now&job_id=<?php echo $job_id; ?>');
            //             $('.spinner-overlay').removeClass('active');
            //             clearInterval( import_int );
            //         }
            //     });
            // }, 8000);
            setTimeout( () => {
                $('.spinner-overlay').addClass('active');
            }, 1000);
        });
    })(jQuery);
</script>