<?php require( 'header.php' ); ?>
<div class="toolbar">
    <div class="toolbar-inner">
        <div class="toolbar-buttons">
            <a href="?quickstart=import_export" class="button button-secondary button-back js-button-back" id="back">
                <i tabindex="300" class="fas fa-arrow-left icon-blue"></i>Back			
            </a>
        </div>
        <div class="toolbar-buttons">
            <a href="?quickstart=export_details" class="button" id="continue-button">
                <i tabindex="200" class="fas fa-arrow-right icon-blue"></i>Continue
            </a>         
        </div>
    </div>
</div>
<div class="body-reset container">
    <div class="quickstart qs_export">
        <h1>Export an Existing Website</h1>
        <legend>Select which website you would like to export.</legend>
        <div class="export-list">
            <div class="toolbar">
                <div class="toolbar-inner">
                    <div class="toolbar-right">
                        <div class="toolbar-search">
                            <form method="get">
                                <input type="hidden" name="quickstart" value="export">
                                <input type="search" class="form-control js-search-input" name="q" value="<?php echo $_GET['q'];?>" title="Search">
                                <button type="submit" class="toolbar-input-submit" title="Search">
                                    <i class="fas fa-magnifying-glass"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="units-table js-units-container">
                <div class="units-table-header">
                    <div class="units-table-cell"></div>
                    <div class="units-table-cell">Name</div>
                    <div class="units-table-cell"></div>
                    <div class="units-table-cell u-text-center">Disk</div>
                </div>

                <?php
                    $user = $_SESSION['user'];
                    exec(HESTIA_CMD . "v-list-web-domains " . $user . " 'json'", $output, $return_var);
                    $websites = json_decode(implode("", $output), true);

                    // Loop through each website and display details
                    $item = 1;
                    foreach( $websites as $domain => $details ) {
                        if ( !empty($_GET['q']) && strpos($domain, $_GET['q']) === false ) continue;
                ?>
                <div class="units-table-row" data-sort-name="<?php echo $domain; ?>">
                    <div class="units-table-cell">
                        <div>
                            <input id="website_radio_<?php echo $item; ?>" tabindex="100"
                                class="website_radio" type="radio" title="Select" 
                                name="domain[]" value="<?php echo $domain; ?>">
                            <label for="website_radio_<?php echo $item; ?>" class="u-hide-desktop">Select</label>
                        </div>
                    </div>
                    <div class="units-table-cell units-table-heading-cell u-text-bold">
                        <span class="u-hide-desktop">Name:</span>
                        <a href="#" class="website_domain">
                            <?php echo $domain; ?>
                        </a>
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
                    } // end foreach( $websites as $domain => $details )
                ?>
            </div>
        </div>
    </div>
</div>
<script>
    (function($) {
        $(function() {
            // Domain click, select radio
            $('.website_domain').on('click', function() {
                $(this).parent().parent().find('input').click();
            });

            // Radio click, select domain and tack on domain to continue button
            $('.website_radio').on('click', function() {
                let domain = $(this).val();
                $('#continue-button').attr('href', '?quickstart=export_details&domain=' + domain);
            });

            // Select the domain or first radio button by default
            <?php 
                if ( isset($_GET['domain']) ) {
                    echo "$('[type=\"radio\"][value=\"" . $_GET['domain'] . "\"]').click();\n";
                } else { 
                    echo "$('.website_radio').first().click();\n";
                }
            ?>
        });
    })(jQuery);
</script>