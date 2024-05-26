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
    $hcpp->quickstart->get_multi_manifests( $job_id );
?>
<form id="import_now" method="POST" action="?quickstart=remove_now&job_id=<?php echo $job_id; ?>">
    <div class="toolbar" style="z-index:100;position:relative;">
        <div class="toolbar-inner">
            <div class="toolbar-buttons">
                <a href="#" class="button button-secondary button-back js-button-back" id="back-button">
                    <i tabindex="300" class="fas fa-stop-circle icon-red"></i>Cancel			
                </a>
            </div>
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
            <div class="form-check u-mb10" id="confirm-remove-opt" style="display:none;">
                <input tabindex="100" class="form-check-input" type="checkbox" name="v-confirm-remove" id="v-confirm-remove">
                <label for="v-confirm-remove">Confirm; all associated resources with the websites<br>
                listed above will be permanently destroyed.</label>
            </div>
        </div>
    </div>
    <input type="hidden" name="manifests" id="manifests" value="">
</form>
<script>
    (function($) {
        $(function() {

            // Check the details key every 6 seconds
            var details_int = setInterval( () => {
                $.ajax({
                    url: '../../pluginable.php?load=quickstart&action=detail_status&job_id=<?php echo $job_id; ?>',
                    type: 'GET',
                    success: function( data ) {
                        try {
                            data = JSON.parse( data );
                        } catch( e ) {
                            $('#error').html( '<p>Error parsing JSON: ' + e + '</p>');
                            $('#error').show();
                        }
                        if ( data.status == 'running' ) {
                            if (data.message != '') $('#status').html(data.message);
                            return;
                        }
                        if ( data.status == 'finished' ) {
                            const manifests = data.message;
                            $('#manifests').val( JSON.stringify( manifests ) );

                            // Display warning and summary
                            let summary = '<legend>The following websites and resources will be permanently removed.';
                            summary += '<br>Check the confirm checkbox below to proceed.';
                            summary += '<div class="action-warn"><i class="fas fa-exclamation-triangle icon-orange"></i><i> ';
                            summary += 'Warning: This action is irreversible.</i></div></legend>';
                            $('#status').html(summary);

                            // Create summary list
                            let html = `<div class="units-table js-units-container">
                                                <div class="units-table-header">
                                                    <div class="units-table-cell">Domain / Aliases</div>
                                                    <div class="units-table-cell u-text-center">Databases</div>
                                                </div>`;
                            for (let i = 0; i < manifests.length; i++) {
                                const manifest = manifests[i];
                                const aliases = manifest.aliases.join(", ");
                                let databases = '';
                                if (manifest.databases.length > 0) {
                                    for (let j = 0; j < manifest.databases.length; j++) {
                                        databases += `<span class="">${manifest.databases[j].TYPE}: ${manifest.databases[j].DATABASE}</span><br>`;
                                    }
                                }
                                html += `<div class="units-table-row" data-sort-name="${manifest.domain}">
                                            <div class="units-table-cell units-table-heading-cell">
                                                <span class="u-text-bold">${manifest.domain}</span><br>
                                                <div class="alias-details">${aliases}</div>
                                            </div>
                                            <div class="units-table-cell u-text-center-desktop">
                                                ${databases}
                                            </div>
                                        </div>`;
                            }
                            html += `
                                </div>`;
                            $('#options').append(html);
                            $('#confirm-remove-opt').show();
                        } else {
                            $('#continue-button').attr('href', '?quickstart=main');
                            if ( data.status == 'error' ) {
                                $('#status').html(data.message);
                            }else{
                                $('#status').html( data.message || 'An unknown error occurred. Please try again.');
                            }
                        }
                        $('.spinner-overlay').removeClass('active');
                        $('#back-button').html('<i tabindex="300" class="fas fa-arrow-left icon-blue"></i>Back');
                        clearInterval( details_int );
                    }
                });
            }, 6000);
            $('.spinner-overlay').addClass('active');

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

            // Eanble continue button if confirm is checked
            $('#v-confirm-remove').on('change', () => {
                if ( $('#v-confirm-remove').is(':checked') ) {
                    $('#continue-button').removeClass('disabled');
                } else {
                    $('#continue-button').addClass('disabled');
                }
            });
        });
    })(jQuery);
</script>