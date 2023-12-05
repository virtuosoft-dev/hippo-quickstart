<?php require( 'header.php' ); ?>
<?php
    $domain = $_GET['domain'];
    $user = $_SESSION['user'];

    // Get a list of databases for the given user

    // Get a list of folders to scan for credentials

    // Omit folders .git, node_modules, wp-content, wp-includes, wp-admin, etc.

    // Check files for credentials .php, .js, .json, .conf, .config, .jsx, .ini, .sh, .xml, .inc, .ts, .cfg, .yml, .yaml, .py, .rb, .env
?>
<div class="toolbar nobar"></div>
<div class="body-reset container">
    <div class="quickstart qs_main">
        <h1>Database details.</h1>
        <legend>We found the following databases in the source code for <?php echo $domain ?>.<br>These will be included in your export. Uncheck to omit them.</legend>
        <div class="export-list">
            <div class="toolbar nobar"></div>
            <div class="units-table js-units-container">
                <div class="units-table-header">
                    <div class="units-table-cell"></div>
                    <div class="units-table-cell">Database Credentials</div>
                    <div class="units-table-cell"></div>
                    <div class="units-table-cell u-text-center">Type</div>
                </div>

                <?php
                    // $user = $_SESSION['user'];
                    // exec(HESTIA_CMD . "v-list-web-domains " . $user . " 'json'", $output, $return_var);
                    // $websites = json_decode(implode("", $output), true);

                    // // Loop through each website and display details
                    // $item = 1;
                    // foreach( $websites as $domain => $details ) {
                    //     if ( !empty($_GET['q']) && strpos($domain, $_GET['q']) === false ) continue;
                ?>
                <div class="units-table-row" data-sort-name="<?php //echo $domain; ?>">
                    <div class="units-table-cell">
                        <div>
                            <input id="db_checkbox_<?php echo $item; ?>" 
                                class="db_checkbox" type="checkbox" title="Select" 
                                name="domain[]" value="<?php //echo $domain; ?>"
                                checked=checked>
                            <label for="db_checkbox_<?php //echo $item; ?>" class="u-hide-desktop">Select</label>
                        </div>
                    </div>
                    <div class="units-table-cell units-table-heading-cell u-text-bold">
                        <span class="u-hide-desktop">DB Credentials:</span>
                        <a href="#" class="website_domain">
                            <?php //echo $domain; ?>
                        </a>
                    </div>
                    <div class="units-table-cell"></div>
                    <div class="units-table-cell u-text-center-desktop">
                        <span class="u-hide-desktop u-text-bold">Disk:</span>
                        <span class="u-text-bold"><?php //echo $details['U_DISK']; ?></span>
                    </div>
                </div>
                <?php
                    //     $item++;
                    // } // end foreach( $websites as $domain => $details )
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
            <a href="?quickstart=export_details" class="button" id="continue-button">
                <i class="fas fa-arrow-right icon-blue"></i>Continue
            </a>         
        </div>
    </div>
</div>
<script>
    (function($) {
        $(function() {
            // // Domain click, select radio
            // $('.website_domain').on('click', function() {
            //     $(this).parent().parent().find('input').click();
            // });

            // // Radio click, select domain and tack on domain to continue button
            // $('.db_checkbox').on('click', function() {
            //     let domain = $(this).val();
            //     $('#continue-button').attr('href', '?quickstart=export_details&domain=' + domain);
            // });

            // // Select the first radio button by default
            // $('.db_checkbox').first().click();
        });
    })(jQuery);
</script>