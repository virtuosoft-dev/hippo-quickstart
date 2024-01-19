<?php require( 'header.php' ); ?>
<?php

    // Validate remove_copy key
    global $hcpp;
    if (false == (isset($_GET['rc_key']) && isset($_SESSION['rc_key']))) return;
    if ($_GET['rc_key'] != $_SESSION['rc_key']) return;
    $rc_key = $_GET['rc_key'];

    // Gather details and write json temp file
    $site_details = $_SESSION[$rc_key . '_site_details'];
    $selected_databases = $_REQUEST['selected_databases'];
    $copy_details = [
        'orig_domain' => '',
        'new_domain' => '',
        'orig_aliases' => [],
        'new_aliases' => [],
        'databases' => [],
        'ref_files' => [],
        'user' => ''
    ];
    foreach( $site_details as $detail ) {
        if ( isset( $detail['DATABASE']) ) {
            $database = $detail['DATABASE'];
            if ( in_array( $database, $selected_databases ) ) {
                $detail['new_DATABASE'] = $_SESSION['user'] . '_' . $hcpp->nodeapp->random_chars(5);
                $detail['new_DBPASSWORD'] = $hcpp->nodeapp->random_chars(20);
                $copy_details['databases'][] = $detail;
            }
        }
        if ( isset( $detail['domain'] ) ) {
            $copy_details['orig_domain'] = $detail['domain'];
            $copy_details['orig_aliases'] = $detail['aliases'];
            $copy_details['ref_files'] = $detail['ref_files'];
            $copy_details['user'] = $_SESSION['user'];
        }
    } 
    $copy_details['new_domain'] = $_REQUEST['v_domain'];
    $new_aliases = explode( "\n", $_REQUEST['v_aliases'] );
    $copy_details['new_aliases'] = array_map( 'trim', $new_aliases );
    $request_file = '/tmp/devstia_copy_' . $rc_key . '.json';
    file_put_contents( $request_file, json_encode($copy_details, JSON_PRETTY_PRINT) );

    // Start copy process asynchonously and get the process id
    $copy_pid = trim( shell_exec(HESTIA_CMD . "v-invoke-plugin quickstart_copy_now " . $rc_key . " > /dev/null 2>/dev/null & echo $!") );
    exit();

    // // Write copy details to temp file
    // global $hcpp;
    // $copy_details = [
    //     "orig_domain" => $domain,
    //     "orig_user" => $user,
    //     "orig_aliases" = 
    // ];
    // $copy_folder = "/home/$user/web/$domain";
    // $_REQUEST['import_folder'] = $import_folder;
    // $_REQUEST['user'] = $_SESSION['user'];
    // $request_file = '/tmp/devstia_import_' . $import_key . '.json';
    // file_put_contents( $request_file, json_encode($_REQUEST, JSON_PRETTY_PRINT) );
    
    // // Start import process asynchonously and get the process id
    // $import_pid = trim( shell_exec(HESTIA_CMD . "v-invoke-plugin quickstart_import_now " . $import_key . " > /dev/null 2>/dev/null & echo $!") );
    // $_SESSION[$import_key . '_pid'] = $import_pid;
?>
<div class="toolbar" style="z-index:100;position:relative;">
    <div class="toolbar-inner">
        <div class="toolbar-buttons">
            <a href="#" class="button button-secondary button-back js-button-back" id="back">
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

            // // Cancel the import
            // $('#back').on('click', (e) => {
            //     e.preventDefault();
            //     $.ajax({
            //         url: '../../pluginable.php?load=quickstart&action=import_cancel&import_key=<?php echo $import_key; ?>',
            //         type: 'GET',
            //         success: function( data ) {
            //             $('#status').html( 'Import cancelled. Click continue.');
            //             $('#back').hide();
            //             $('#options').hide();
            //             $('#continue-button').removeClass('disabled');
            //             $('#continue-button').attr('href', '?quickstart=main');
            //             $('.spinner-overlay').removeClass('active');
            //         }
            //     });
            // });
            // Check the import key every 8 seconds
            var import_int = setInterval( () => {
                $.ajax({
                    url: '../../pluginable.php?load=quickstart&action=import_result&import_key=<?php echo $import_key; ?>',
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
                            $('#options').hide();
                            $('#continue-button').removeClass('disabled');
                            $('#continue-button').attr('href', '?quickstart=main');
                            $('.spinner-overlay').removeClass('active');
                            clearInterval( import_int );
                        }
                    }
                }); 
            }, 8000);
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
            //             $('#continue-button').attr('href', '?quickstart=import_now&import_key=<?php echo $import_key; ?>');
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