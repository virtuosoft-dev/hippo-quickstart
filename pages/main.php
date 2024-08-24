<?php require( 'header.php' ); 

// Cancel any existing job
if ( isset( $_GET['job_id'] ) ) {
    $job_id = $_GET['job_id'];
    $hcpp->quickstart->cancel_job( $job_id );
}
?>
<div class="toolbar">
    <div class="toolbar-inner">
        <div class="toolbar-buttons">
        </div>
        <div class="toolbar-buttons">
            <a href="?quickstart=create" class="button" id="continue-button">
                <i tabindex="300" class="fas fa-arrow-right icon-blue"></i>Continue
            </a>         
        </div>
    </div>
</div>
<div class="body-reset container">
    <div class="quickstart qs_main">
        <h1>Devstia makes it easy to create websites.</h1>
        <legend>Choose an option &amp; click the "Continue" button:</legend>
        <p>
        <input name="qsOption" type="radio" id="create" checked="checked" tabindex="100"/>
        <label for="create">Create a new website.</label>
        </p>
        <p>
        <input name="qsOption" type="radio" id="remove_copy" tabindex="100"/>
        <label for="remove_copy">Remove or copy a website.</label>
        </p>
        <p>
        <input name="qsOption" type="radio" id="import_export" tabindex="100"/>

        
        <label for="import_export">Import or export a website.</label>
        </p>
    </div>
</div>
<script>
    (function($){
        $(function() {
            $('#continue-button').click(function() {
                $('.spinner-overlay').addClass('active');
            });

            // Update the continue button href based on selected qsOption
            $('input[name="qsOption"]').on('change', (e) => {
                let qsOption = $('input[name="qsOption"]:checked').attr('id');
                $('#continue-button').attr('href', '?quickstart=' + qsOption);
            });
        });
    })(jQuery);
</script>
