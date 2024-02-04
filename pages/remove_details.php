<?php require( 'header.php' ); ?>
<?php

    // Check for selected domains
    if ( !isset( $_GET['domain'] ) ) {
        header( 'Location: ?quickstart=main' );
        exit;
    }

    // Create a new job
    $job_id = $hcpp->quickstart->create_job();

    // Get selected domain details asynchronously
    // $manifest = $hcpp->quickstart->get_manifest( $_SESSION['user'], $_GET['domain'] );
    // $hcpp->quickstart->set_job_data( $job_id, 'manifest', $manifest );

?>
<form id="import_now" method="POST" action="?quickstart=remove_now&job_id=<?php echo $job_id; ?>">
    <div class="toolbar" style="z-index:100;position:relative;">
        <div class="toolbar-inner">
            <div class="toolbar-buttons">
                <a href="#" class="button button-secondary button-back js-button-back" id="back-button">
                    <i tabindex="300" class="fas fa-stop-circle icon-red"></i>Cancel			
                </a>
            </div>
            <!-- <div class="toolbar-buttons">
                <a href="?quickstart=remove_copy&mode=remove&domain=<?php echo $_GET['domain']; ?>&job_id=<?php echo $job_id;?>" class="button button-secondary button-back js-button-back" id="back-button">
                    <i tabindex="300" class="fas fa-arrow-left icon-blue"></i>Back			
                </a>
            </div> -->
            <div class="toolbar-buttons">
                <button tabindex="200" class="button disabled" type="submit" id="continue-button"><i class="fas fa-arrow-right icon-blue"></i>Continue</button>
            </div>
        </div>
    </div>
    <div class="body-reset container">
        <div class="quickstart qs_remove_details">

            <h1>Remove Details</h1>
            <legend id="status">Please wait. Gathering website details.</legend>
            <div id="options"></div>

            <!-- <h1>Remove Details</h1>
                <legend>
                    The following web sites and associated resources will be destroyed.<br/>
                    <i class="fas fa-exclamation-triangle icon-orange"></i><i> Warning: This action is irreversible.</i>
                </legend>
                <div class="u-mb10">
                    <label for="v_domain" class="form-label">Domains</label>
                </div> -->
                <?php 
                    // $aliases = implode( "\n", $manifest['aliases'] );
                    // if ( trim( $aliases ) != '' ) {
                    //     echo '<div class="u-mb10">';
                    //     echo '<label for="v_aliases" class="form-label">Aliases</label>';
                    //     echo '<textarea class="form-control" name="v_aliases" id="v_aliases" tabindex="100">' . $aliases . '</textarea>';
                    //     echo '</div>';
                    // }
                ?>
                <!-- <br/>
                <div class="u-mb10">
                    <label for="v_domain" class="form-label">Databases</label>
                </div> -->
            <!-- <h3 tabindex="100"><i class="fas fa-caret-right"></i> Database Details</h3>
            <div id="database-details" style="display:none;">
                <legend>The following databases were referenced in files for <?php echo $manifest['domain'] ?>.<br>
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
                            $databases = $manifest['databases'];
                            $item = 1;
                            foreach( $databases as $details) {
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
                            } // end foreach( $databases as $details)
                        ?>
                    </div>
                </div>
            </div> -->
        </div>
    </div>
</form>
<script>
    (function($) {
        $(function() {

            // // Expand/collapse database details
            // $('#database-details').hide();
            // $('#database-details').prev().on('click', function() {
            //     $('#database-details').slideToggle();
            //     $(this).find('i').toggleClass('fa-caret-right fa-caret-down');
            // });
            
            // // Domain click, select checkbox
            // $('.database').on('click', function() {
            //     $(this).parent().parent().find('input').click();
            // });

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
                $('#continue-button').attr('href', '?quickstart=copy_now&domain=<?php echo $manifest['domain']; ?>&dbs=' + dbs + '&job_id=<?php echo $job_id;?>');
            }
            checkDbs();
            // setTimeout(()=>{
            //     $('#v_domain').focus().select();
            // }, 500);
            var details_int = 0;

            // Cancel gathering details
            $('#back-button').on('click', (e) => {
                clearInterval( details_int );
                e.preventDefault();
                window.location = '?quickstart=remove_copy&mode=remove&domain=<?php echo $_GET['domain']; ?>&job_id=<?php echo $job_id;?>';
            });

            $('#continue-button').on('click', (e) => {
                if ( $('#continue-button').hasClass('disabled') ) {
                    e.preventDefault();
                }
            });

            setTimeout( () => {
                $('.spinner-overlay').addClass('active');
            }, 1000);
        });
    })(jQuery);
</script>