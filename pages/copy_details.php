<?php require( 'header.php' ); ?>
<?php

    // Validate remove_copy key
    if (false == (isset($_GET['rc_key']) && isset($_SESSION['rc_key']))) return;
    if ($_GET['rc_key'] != $_SESSION['rc_key']) return;
    $rc_key = $_GET['rc_key'];

    // Sanitize domain
    $domain = $_GET['domain'];
    $domain = preg_replace( '/[^a-zA-Z0-9\.\-]/', '', $domain) ;
    $user = $_SESSION['user'];
    exec(HESTIA_CMD . "v-invoke-plugin quickstart_site_details " . $user . " " . $domain, $output, $return_var);
    $site_details = json_decode( implode( "", $output ), true );
    $_SESSION[$rc_key . '_site_details'] = $site_details;
?>
<form id="import_now" method="POST" action="?quickstart=copy_now&rc_key=<?php echo $rc_key; ?>">
    <div class="toolbar">
        <div class="toolbar-inner">
            <div class="toolbar-buttons">
                <a href="?quickstart=remove_copy&mode=copy&domain=<?php echo $domain; ?>&rc_key=<?php echo $rc_key;?>" class="button button-secondary button-back js-button-back" id="back">
                    <i tabindex="300" class="fas fa-arrow-left icon-blue"></i>Back			
                </a>
            </div>
            <div class="toolbar-buttons">
                <button tabindex="200" class="button" type="submit" id="continue-button"><i class="fas fa-arrow-right icon-blue"></i>Continue</button>
            </div>
        </div>
    </div>
    <div class="body-reset container">
        <div class="quickstart qs_site_details">
            <h1>Copy Details</h1>
                <legend>Fill in options. <?php if ( is_dir('/home/devstia') ) echo "<i>Devstia Preview should use a <b>.dev.pw</b> TLD.</i>"; ?></legend>
                <div class="u-mb10">
                    <label for="v_domain" class="form-label">Domain</label>
                    <input type="text" class="form-control" name="v_domain" id="v_domain" value="<?php echo $domain; ?>" required="" tabindex="100">
                </div>
                <?php 
                    foreach( $site_details as $data ) {
                        if ( isset( $data['aliases'] ) ) {
                            $aliases = implode("\n", $data['aliases']);
                            if ( trim( $aliases ) != '' ) {
                                echo '<div class="u-mb10">';
                                echo '<label for="v_aliases" class="form-label">Aliases</label>';
                                echo '<textarea class="form-control" name="v_aliases" id="v_aliases" tabindex="100">' . $aliases . '</textarea>';
                                echo '</div>';
                                break;
                            }
                        }
                    }
                ?>
            <br/>
            <h3 tabindex="100"><i class="fas fa-caret-right"></i> Database Details</h3>
            <div id="database-details" style="display:none;">
                <legend>The following databases were referenced in files for <?php echo $domain ?>.<br>
                These will be copied and file references updated. Uncheck to omit them.</legend>
                <div class="copy-list">
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
                            $item = 1;
                            foreach ( $site_details as $db => $details ) {
                                if ( !isset( $details['DATABASE'] ) ) continue;
                        ?>
                        <div class="units-table-row">
                            <div class="units-table-cell">
                                <div>
                                    <input id="db_checkbox_<?php echo $item; ?>" tabindex="100"
                                        class="db_checkbox" type="checkbox" title="Select" 
                                        name="selected_databases[]" value="<?php echo $details['DATABASE']; ?>"
                                        <?php
                                            if ( isset( $_GET['dbs'] ) ) {
                                                if ( in_array( $details['DATABASE'], explode( ",", $_GET['dbs'] ) ) ) {
                                                    echo 'checked';
                                                }
                                            } else {
                                                echo 'checked';
                                            }
                                        ?>
                                    >
                                    <label for="db_checkbox_<?php echo $item; ?>" class="u-hide-desktop">Include</label>
                                </div>
                            </div>
                            <div class="units-table-cell units-table-heading-cell u-text-bold">
                                <span class="u-hide-desktop">DB / Password:</span>
                                <a href="#" class="database">
                                    <?php 
                                        global $hcpp;
                                        echo $details['DATABASE'] . ' / ' . $details['DBPASSWORD'];
                                        foreach( $details['ref_files'] as $file ) {
                                            echo '<br><span class="ref-files">' . $file . '</span>';
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
                            } // end foreach( $site_details as $db => $details )
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
<script>
    (function($) {
        $(function() {

            // Expand/collapse database details
            $('#database-details').hide();
            $('#database-details').prev().on('click', function() {
                $('#database-details').slideToggle();
                $(this).find('i').toggleClass('fa-caret-right fa-caret-down');
            });
            
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
                $('#continue-button').attr('href', '?quickstart=copy_now&domain=<?php echo $domain; ?>&dbs=' + dbs + '&rc_key=<?php echo $rc_key;?>');
            }
            checkDbs();
            setTimeout(()=>{
                $('#v_domain').focus().select();
            }, 500);
        });
    })(jQuery);
</script>