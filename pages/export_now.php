<?php require( 'header.php' ); ?>
<?php
    // Omit databases that are not selected
    global $hcpp;
    $db_details = $_SESSION['db_details'];
    $domain = $_GET['domain'];
    $user = $_SESSION['user'];
    $dbs = [];
    $web_detail = $hcpp->run( "v-list-web-domain $user $domain json" )[$domain];
    $main_ref_files = [];
    foreach( $db_details as $db => $details ) {
        if ( !isset( $details['DATABASE'] ) ) {
            $main_ref_files = $details['ref_files'];
            continue;
        }
        if ( in_array( $details['DATABASE'], explode( ",", $_GET['dbs'] ) ) ) {
            $dbs[] = $details;

            // Cull user folder from ref_files
            foreach( $dbs as $key => $db ) {
                foreach( $db['ref_files'] as $key2 => $file ) {
                    if ( substr( $file, 0, 1 ) == '.' ) continue;
                    $dbs[$key]['ref_files'][$key2] = $file;
                }
            }
        }
    }

    // Create a manifest file for the export
    $dtstamp = date( 'Y-m-d-His' );
    $json_file = 'devstia_export_' . $dtstamp . '.json';
    $zip_file = $domain . '_' . $dtstamp . '.zip';
    $devstia_manifest = [
        'alias' => $web_detail['ALIAS'],
        'backend' => $web_detail['BACKEND'],
        'databases' => $dbs,
        'domain' => $domain,
        'proxy' => $web_detail['PROXY'],
        'proxy_ext' => $web_detail['PROXY_EXT'],
        'template' => $web_detail['TPL'],
        'user' => $user,
        'zip_file' => $zip_file,
        'ref_files' => $main_ref_files,
        'export_options' => $_POST['export_options'] ?? '',
        'export_adv_options' => json_decode( $_POST['export_adv_options'] ?? ''),
    ];
    $_SESSION['devstia_manifest'] = $devstia_manifest;
    file_put_contents( '/tmp/' . $json_file, json_encode( $devstia_manifest, JSON_PRETTY_PRINT ) );

    // Start export process asynchonously and get the process id
    $export_pid = trim( shell_exec(HESTIA_CMD . "v-invoke-plugin quickstart_export_zip " . $json_file . " > /dev/null 2>/dev/null & echo $!") );
    $export_key = $hcpp->nodeapp->random_chars( 16 );
    $_SESSION['export_key'] = $export_key;
    $_SESSION[$export_key . '_pid'] = $export_pid;
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
                <i class="fas fa-flag-checkered icon-blue"></i>Finished
            </a>         
        </div>
    </div>
</div>
<div class="body-reset container">
    <div class="quickstart qs_main">
        <h1>Export <?php echo $domain; ?></h1>
        <legend id="status">Please wait. Copying and compressing files.</legend>
        <div id="error" style="display: none;"></div>
    </div>
</div>
<script>
    (function($){
        $(function() {

            // Cancel the export
            $('#back').on('click', (e) => {
                e.preventDefault();
                $.ajax({
                    url: '../../pluginable.php?load=quickstart&action=export_cancel&export_key=<?php echo $export_key; ?>',
                    type: 'GET',
                    success: function( data ) {
                        $('#error').html( '<p>Export canceled.</p>');
                        $('#error').show();
                        $('#back').hide();
                        $('#continue-button').removeClass('disabled');
                        $('#continue-button').attr('href', '?quickstart=main');
                        $('.spinner-overlay').removeClass('active');
                    }
                });
            });

            // Check the export_key every 8 seconds
            var export_key = '<?php echo $export_key; ?>';
            var export_int = setInterval( () => {
                $.ajax({
                    url: '../../pluginable.php?load=quickstart&action=export_status&export_key=' + export_key,
                    type: 'GET',
                    success: function( data ) {
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
                                <strong><a href="../../pluginable.php?load=quickstart&action=download&export_key=<?php echo $export_key; ?>">
                                <?php
                                    $zip_file = $json_file;
                                    $zip_file = $hcpp->delRightMost( $zip_file, '.json' ) . '.zip';
                                    $zip_file = $domain . $hcpp->delLeftMost( $zip_file, 'devstia_export' );
                                    echo $zip_file;
                                ?>
                                </a></strong>
                            </div>
                            <br>
                            <?php 
                                if ( $user == 'devstia' ) {
                                    echo "<p><strong>Devstia Preview:</strong></p>";
                                    echo "<p>You can also find the file in your Devstia drive's \"exports\" folder.</p>";
                                }
                            ?>`);                            
                        } else {
                            $('#status').html( '<p>An unknown error occurred. Please try again.</p>');
                        }
                        $('#back').hide();
                        $('#continue-button').removeClass('disabled');
                        $('#continue-button').attr('href', '?quickstart=main');
                        $('.spinner-overlay').removeClass('active');
                        clearInterval( export_int );
                    }
                });
            }, 8000);

            // Update the continue button href based on selected qsOption
            $('input[name="qsOption"]').on('change', (e) => {
                let qsOption = $('input[name="qsOption"]:checked').attr('id');
                $('#continue-button').attr('href', '?quickstart=' + qsOption);
            });
            setTimeout( () => {
                $('.spinner-overlay').addClass('active');
            }, 1000);
        });
    })(jQuery);
</script>