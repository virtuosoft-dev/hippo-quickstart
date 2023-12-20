<?php require( 'header.php' ); ?>
<?php
    // Omit databases that are not selected
    global $hcpp;
    $db_details = $_SESSION['db_details'];
    $domain = $_GET['domain'];
    $user = $_SESSION['user'];
    $dbs = [];
    foreach( $db_details as $db => $details ) {
        if ( in_array( $details['DATABASE'], explode( ",", $_GET['dbs'] ) ) ) {
            $dbs[] = $details;

            // Cull user folder from ref_files
            foreach( $dbs as $key => $db ) {
                foreach( $db['REF_FILES'] as $key2 => $file ) {
                    if ( substr( $file, 0, 1 ) == '.' ) continue;
                    $dbs[$key]['REF_FILES'][$key2] = "." . $hcpp->delLeftMost( $file, $domain );
                }
            }
        }
    }

    // Create a manifest file for the export
    $dtstamp = date( 'Y-m-d-His' );
    $json_file = 'devstia_export_' . $dtstamp . '.json';
    $zip_file = $domain . '_' . $dtstamp . '.zip';
    $proxy = "";
    $devstia_manifest = [
        'zip_file' => $zip_file, 
        'user' => $user,
        'domain' => $domain,
        'proxy' => $proxy,
        'databases' => $dbs,
    ];
    $_SESSION['devstia_manifest'] = $devstia_manifest;
    file_put_contents( '/tmp/' . $json_file, json_encode( $devstia_manifest, JSON_PRETTY_PRINT ) );

    // Start export process asynchonously and get the process id
    $export_pid = trim( shell_exec(HESTIA_CMD . "v-invoke-plugin quickstart_export_zip " . $json_file . " > /dev/null 2>/dev/null & echo $!") );
    $export_key = $hcpp->nodeapp->random_chars( 16 );
    $_SESSION['export_key'] = $export_key;
    $_SESSION['export_pid'] = $export_pid;
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
        <h1>Exporting <?php echo $domain; ?>.</h1>
        <legend id="please_wait">Please wait. Copying and compressing files.</legend>
        <div id="error" style="display: none;"></div>
        <div id="finished" style="display:none;">
            <p>Export finished. You can download the exported archive at:</p>
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
            ?>
        </div>
    </div>
</div>
<script>
    (function($){
        $(function() {

            // Cancel the export
            $('#back').on('click', (e) => {
                e.preventDefault();
                $.ajax({
                    url: '../../pluginable.php?load=quickstart&action=cancel&export_key=<?php echo $export_key; ?>',
                    type: 'GET',
                    success: function( data ) {
                        $('#error').html( '<p>Export canceled.</p>');
                        $('#error').show();
                        $('#back').hide();
                        $('#continue-button').removeClass('disabled');
                        $('#continue-button').attr('href', '?quickstart=main');
                        $('#please_wait').hide();
                        $('.spinner-overlay').removeClass('active');
                    }
                });
            });

            // Check the export_key every 10 seconds
            var export_key = '<?php echo $export_key; ?>';
            var export_int = setInterval( () => {
                $.ajax({
                    url: '../../pluginable.php?load=quickstart&action=export_status&export_key=' + export_key,
                    type: 'GET',
                    success: function( data ) {
                        try {
                            data = JSON.parse( data );
                            if ( data.status == 'running' ) return;
                            if ( data.status == 'finished' ) {
                                $('#finished').show();
                            } else {
                                $('#error').html( '<p>An unknown error occurred. Please try again.</p>');
                                $('#error').show();
                            }
                        } catch( e ) {
                            $('#error').html( '<p>Error parsing JSON: ' + e + '</p>');
                            $('#error').show();
                        }
                        $('#back').hide();
                        $('#continue-button').removeClass('disabled');
                        $('#continue-button').attr('href', '?quickstart=main');
                        $('#please_wait').hide();
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