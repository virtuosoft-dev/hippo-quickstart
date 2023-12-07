<?php require( 'header.php' ); ?>
<?php
    // Sanitize domain
    $domain = $_GET['domain'];
    $domain = preg_replace('/[^a-zA-Z0-9\.\-]/', '', $domain);
    $user = $_SESSION['user'];
    exec(HESTIA_CMD . "v-invoke-plugin quickstart_export_dbs " . $user . " " . $domain, $output, $return_var);
    $db_details = json_decode(implode("", $output), true);
    $_SESSION['db_details'] = $db_details;
?>
<div class="toolbar nobar"></div>
<div class="body-reset container">
    <div class="quickstart qs_main">
        <h1>Database details.</h1>
        <legend>The following databases were referenced in files for <?php echo $domain ?>.<br>These will be included in your export. Uncheck to omit them.</legend>
        <div class="export-list">
            <div class="toolbar nobar"></div>
            <div class="units-table js-units-container">
                <div class="units-table-header">
                    <div class="units-table-cell"></div>
                    <div class="units-table-cell">Database / Password</div>
                    <div class="units-table-cell"></div>
                    <div class="units-table-cell u-text-center">Type</div>
                    <div class="units-table-cell"></div>
                    <div class="units-table-cell u-text-center">Disk</div>
                </div>

                <?php
                    // Loop through each database and display details
                    foreach ( $db_details as $db => $details ) {
                        $item = 1;
                ?>
                <div class="units-table-row">
                    <div class="units-table-cell">
                        <div>
                            <input id="db_checkbox_<?php echo $item; ?>" 
                                class="db_checkbox" type="checkbox" title="Select" 
                                name="domain[]" value="<?php echo $details['DATABASE']; ?>"
                                checked=checked>
                            <label for="db_checkbox_<?php echo $item; ?>" class="u-hide-desktop">Include</label>
                        </div>
                    </div>
                    <div class="units-table-cell units-table-heading-cell u-text-bold">
                        <span class="u-hide-desktop">DB / Password:</span>
                        <a href="#" class="database">
                            <?php 
                                global $hcpp;
                                echo $details['DATABASE'] . ' / ' . $details['DBPASSWORD'];
                                foreach( $details['REF_FILES'] as $file ) {
                                    echo '<br><span class="ref-files">./' . $hcpp->delLeftMost( $file, "/$domain/" ) . '</span>';
                                }
                            ?>
                        </a>
                    </div>
                    <div class="units-table-cell"></div>
                    <div class="units-table-cell u-text-center-desktop">
                        <span class="u-hide-desktop u-text-bold">Type:</span>
                        <span class="u-text-bold"><?php echo $details['TYPE']; ?></span>
                    </div>
                    <div class="units-table-cell"></div>
                    <div class="units-table-cell u-text-center-desktop">
                        <span class="u-hide-desktop u-text-bold">Disk:</span>
                        <span class="u-text-bold"><?php echo $details['U_DISK']; ?></span>
                        <span class="u-text-small">mb</span>
                    </div>
                </div>
                <?php
                        $item++;
                    } // end foreach( $db_details as $db => $details )
                ?>
            </div>
        </div>
    </div>
</div>
<div class="toolbar">
    <div class="toolbar-inner">
        <div class="toolbar-buttons">
            <a href="?quickstart=export" class="button button-secondary button-back js-button-back" id="back">
                <i class="fas fa-arrow-left icon-blue"></i>Back			
            </a>
        </div>
        <div class="toolbar-buttons">
            <a href="?quickstart=export_now" class="button" id="continue-button">
                <i class="fas fa-arrow-right icon-blue"></i>Continue
            </a>         
        </div>
    </div>
</div>
<script>
    (function($) {
        $(function() {
            // Domain click, select checkbox
            $('.database').on('click', function() {
                $(this).parent().parent().find('input').click();
            });

            // Checkbox click, update dbs to tack on to continue button
            $('.db_checkbox').on('click', function() {
                checkDbs();
            });

            function checkDbs() {
                let dbs = [];
                $('.db_checkbox').each(function() {
                    if ( $(this).is(':checked') ) {
                        dbs.push($(this).val());
                    }
                });
                dbs = dbs.join(',');
                $('#continue-button').attr('href', '?quickstart=export_now&domain=<?php echo $domain; ?>&dbs=' + dbs);
            }
            checkDbs();
        });
    })(jQuery);
</script>