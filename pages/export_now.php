<?php require( 'header.php' ); ?>
<?php
    // Omit databases that are not selected
    global $hcpp;
    $db_details = $_SESSION['db_details'];
    $domain = $_GET['domain'];
    $dbs = [];
    foreach( $db_details as $db => $details ) {
        if ( in_array( $details['DATABASE'], explode( ",", $_GET['dbs'] ) ) ) {
            $dbs[] = $details;

            // Cull user folder from ref_files
            foreach( $dbs as $key => $db ) {
                foreach( $db['REF_FILES'] as $key2 => $file ) {
                    $dbs[$key]['REF_FILES'][$key2] = "." . $hcpp->delLeftMost( $file, $domain );
                }
            }
        }
    }

    // Create a manifest file for the export
    $json_file = 'devstia_export_' . date( 'Y-m-d-His' ) . '.json';
    $proxy = "";
    $devstia_manifest = [
        'user' => $_SESSION['user'],
        'domain' => $_GET['domain'],
        'proxy' => $proxy,
        'databases' => $dbs,
    ];
    file_put_contents( '/tmp/' . $json_file, json_encode( $devstia_manifest, JSON_PRETTY_PRINT ) );

    // Start export process asynchonously and get the process id
    $export_pid = shell_exec(HESTIA_CMD . "v-invoke-plugin quickstart_export_zip " . $json_file . " > /dev/null 2>/dev/null & echo $!");
?>
<div class="toolbar nobar"></div>
<div class="body-reset container">
    <div class="quickstart qs_main">
        <h1>Exporting <?php echo $domain; ?>.</h1>
        <legend>Please wait.</legend>
        <br><br><br><br><br>
    </div>
</div>
<div class="toolbar" style="z-index:100;position:relative;">
    <div class="toolbar-inner">
        <div class="toolbar-buttons">
            <a href="?quickstart=cancel" class="button button-secondary button-back js-button-back" id="back">
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
<script>
    (function($){
        $(function() {
            // Check the export_id every 5 seconds
            var export_id = '<?php echo $export_id; ?>';
            console.log(export_id);

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