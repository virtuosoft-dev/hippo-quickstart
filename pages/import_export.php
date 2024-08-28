<?php require( 'header.php' ); ?>
<div class="toolbar">
    <div class="toolbar-inner">
        <div class="toolbar-buttons">
            <a href="?quickstart=main" class="button button-secondary button-back js-button-back" id="back-button">
                <i tabindex="300" class="fas fa-arrow-left icon-blue"></i>Back			
            </a>
        </div>
        <div class="toolbar-buttons">
            <a href="?quickstart=import" class="button" id="continue-button">
                <i tabindex="200" class="fas fa-arrow-right icon-blue"></i>Continue
            </a>         
        </div>
    </div>
</div>
<div class="body-reset container">
    <div class="quickstart qs_export">
        <h1>Import or Export a Website</h1>
        <legend>Which operation would you like to do?</legend>
        <p>
        <input name="qsOption" type="radio" id="import" checked="checked" tabindex="100"/>
        <label for="import">Import a website.</label>
        </p>
        <p>
        <input name="qsOption" type="radio" id="export" tabindex="100"/>
        <label for="export">Export an existing website.</label>
        </p>
        <p>
        <input name="qsOption" type="radio" id="export_view" tabindex="100"/>
        <label for="export_view">View list of exported websites.</label>
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