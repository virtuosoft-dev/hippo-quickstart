<?php require( 'header.php' ); ?>
<div class="toolbar">
    <div class="toolbar-inner">
        <div class="toolbar-buttons">
            <a href="?quickstart=main" class="button button-secondary button-back js-button-back" id="back-button">
                <i tabindex="300" class="fas fa-arrow-left icon-blue"></i>Back			
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
    <div class="quickstart qs_remove_copy">
        <h1>Remove or Copy a Website</h1>
        <legend>Choose one or more websites from the list of websites:</legend>
        <div id="info-copy" class="alert alert-info u-mb10" role="alert">
            <i class="fas fa-info"></i>
            <p>Copying a website copies the assoicated<br>
            files and databases.</p>
        </div>
        <div id="warn-remove" class="alert alert-danger u-mb10" role="alert" style="display:none;">
            <i class="fas fa-exclamation-triangle"></i>
            <p>Removing website(s) deletes all the files and<br>
               associated databases.</p>
        </div>
        <div class="remove-copy-list">
            <div class="toolbar">
                <div class="toolbar-inner">
                    <div class="toolbar-right">
                        <div class="toolbar-search">
                            <form method="get">
                                <input type="hidden" name="quickstart" value="remove_copy">
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
                            <input id="website_<?php echo $item; ?>" 
                                class="website_check js-unit-checkbox" type="checkbox" title="Select" 
                                name="domain[]" value="<?php echo $domain; ?>" tabindex="100">
                            <label for="website_<?php echo $item; ?>" class="u-hide-desktop">Select</label>
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
            <br>
            <div id="action">
                <div class="form-check u-mb10">
                    <input id="v_copy_website" class="website_radio" type="radio" title="Select" name="mode[]" value="copy" tabindex="100" checked>
                    <label for="v_copy_website">Copy website</label>
                </div>
                <div class="form-check u-mb10">
                    <input id="v_remove_website" class="website_radio" type="radio" title="Select" name="mode[]" tabindex="100" value="remove">
                    <label for="v_remove_website">Remove website(s)</label>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    (function($) {
        $(function() {

            // Toggle infobox based on copy/remove radio button
            $('.website_radio').on('click', function() {
                if ( $(this).val() == 'copy' ) {
                    $('#info-copy').show();
                    $('#warn-remove').hide();
                } else {
                    $('#info-copy').hide();
                    $('#warn-remove').show();
                }
                updateContinueButton();
            });

            // Domain click, select radio
            $('.website_domain').on('click', function() {
                $(this).parent().parent().find('input').click();
            });

            // Radio click, select domain
            $('.website_check').on('click', function() {

                // Enforce single selection for copy mode
                if ( $('#v_copy_website').prop('checked') ) {
                    $('.website_check').prop('checked', false);
                    $(this).prop('checked', true);
                }
                updateContinueButton();
            });

            // Select any domains that were passed in the URL
            if ( typeof getUrlVars()['domain'] !== 'undefined' ) {
                let domains = getUrlVars()['domain'].split(',');
                $('.website_check').each(function() {
                    if ( domains.indexOf($(this).val()) > -1 ) {
                        $(this).prop('checked', true);
                    }
                });
            }
            
            // Restore mode from back button
            if ( typeof getUrlVars()['mode'] !== 'undefined' ) {
                if ( getUrlVars()['mode'] == 'copy' ) {
                    $('#v_copy_website').prop('checked', true).click();
                }else{
                    $('#v_remove_website').prop('checked', true).click();
                }
            }else{
                updateContinueButton();
            }

            function getUrlVars() {
                var vars = {};
                var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
                    vars[key] = value;
                });
                return vars;
            }

            function updateContinueButton() {

                // Check if more than one domain clicked
                let domains = [];
                let lastChecked = null;
                $('.website_check:checked').each(function() {
                    domains.push($(this).val());
                    lastChecked = $(this);
                });

                // Select last domain only if in copy mode
                if (domains.length > 1 && $('#v_copy_website').prop('checked')) {
                    $('.website_check').prop('checked', false);
                    lastChecked.prop('checked', true);
                }

                // Update continue button
                if (domains.length == 0) {
                    $('#continue-button').addClass('disabled');
                    $('#continue-button').attr('href', '#');
                } else {
                    $('#continue-button').removeClass('disabled');
                    if ( $('#v_remove_website').prop('checked') ) {
                        $('#continue-button').attr('href', '?quickstart=remove_details&domain=' + domains.join(','));
                    } else {
                        $('#continue-button').attr('href', '?quickstart=copy_details&domain=' + domains[0]);
                    }
                }
            }
        });
    })(jQuery);
</script>