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
                <i class="fas fa-arrow-left icon-blue"></i>Back			
            </a>
        </div>
        <div class="toolbar-buttons">
            <a href="?quickstart=main" class="button" id="continue-button">
                <i class="fas fa-arrow-right icon-blue"></i>Continue
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
                echo '<ul>';
                foreach ( $exports as $export ) {
                    $export = basename( $export );
                    echo '<li><a href="/exports/' . $export . '">' . $export . '</a></li>';
                }
                echo '</ul>';
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

            // Update the continue button href based on selected qsOption
            $('input[name="qsOption"]').on('change', (e) => {
                let qsOption = $('input[name="qsOption"]:checked').attr('id');
                $('#continue-button').attr('href', '?quickstart=' + qsOption);
            });
        });
    })(jQuery);
</script>