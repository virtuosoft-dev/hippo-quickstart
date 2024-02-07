<?php require( 'header.php' ); ?>
<?php
    if ( !isset( $_SESSION['user'] ) ) {
        header( 'Location: ?quickstart=login' );
        exit;
    }
    $user = $_SESSION['user'];
?>
<div class="toolbar">
    <div class="toolbar-inner">
        <div class="toolbar-buttons">
            <a href="?quickstart=import_export" class="button button-secondary button-back js-button-back" id="back-button">
                <i tabindex="300" class="fas fa-arrow-left icon-blue"></i>Back			
            </a>
        </div>
        <div class="toolbar-buttons">
            <a href="?quickstart=main" class="button" id="continue-button">
                <i tabindex="200" class="fas fa-arrow-right icon-blue"></i>Continue
            </a>         
        </div>
    </div>
</div>
<div class="body-reset container">
    <div class="quickstart qs_export_view">
        <h1>View of Exported Websites</h1>
        <legend>
            Download, delete, or share your exported creation.
        </legend>
        <?php
            // List zip files from the user's exports directory with a download link
            $exports = glob( "/home/$user/web/exports/*.zip" );
            if ( count( $exports ) > 0 ) {
                echo '<div class="units-table js-units-container">
                        <div class="units-table-header">
                        <div class="units-table-cell"></div>
                            <div class="units-table-cell">Export Archive</div>
                            <div class="units-table-cell u-text-center"></div>
                        </div>';
                foreach ( $exports as $export ) {
                    $export = basename( $export );
                    echo '<div class="units-table-row" data-sort-name="<?php echo $domain; ?>">
                            <div class="units-table-cell"></div>
                            <div class="units-table-cell">
                                <a href="../../pluginable.php?load=quickstart&action=download&file=' . $export . '">
                                    <i tabindex="100" class="fas fa-download"></i> ' . $export . '
                                </a>
                            </div>
                            <div class="units-table-cell">
                                <a tabindex="150" href="../../pluginable.php?load=quickstart&action=delete_export&file=' . $export . '">
                                    <i tabindex="100" class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                         </div>';
                }
                echo '</div>';
            } else {
                echo '<p>No exports found.</p>';
            }
        ?>
        <p>
        <?php
            if ( $_SESSION['user'] == 'devstia' ) {
                echo "<br><p><strong>Devstia Preview:</strong></p>";
                echo '<p>You can also find exports in your <a href="https://devstia.com/docs/devstia-drive" target="_blank">Devstia drive\'s "exports" folder.</p>';
            }
        ?>
        </p>
    </div>
</div>
<script>
    (function($){
        $(function() {
            // Enable keyboard navigation
            $(document).on('keypress', 'i.fas.fa-download', function(e) {
                if (e.which == 13) {
                    $(this).parent('a')[0].click();
                }
            });
            $(document).on('keypress', 'i.fas.fa-trash', function(e) {
                if (e.which == 13) {
                    $(this).parent('a')[0].click();
                }
            });

            // Update the continue button href based on selected qsOption
            $('input[name="qsOption"]').on('change', (e) => {
                let qsOption = $('input[name="qsOption"]:checked').attr('id');
                $('#continue-button').attr('href', '?quickstart=' + qsOption);
            });
        });
    })(jQuery);
</script>