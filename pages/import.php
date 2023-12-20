<?php require( 'header.php' ); ?>
<div class="toolbar">
    <div class="toolbar-inner">
        <div class="toolbar-buttons">
            <a href="?quickstart=import_export" class="button button-secondary button-back js-button-back" id="back">
                <i class="fas fa-arrow-left icon-blue"></i>Back			
            </a>
        </div>
        <div class="toolbar-buttons">
            <a href="?quickstart=import_now" class="button" id="import-button">
                <i class="fas fa-arrow-right icon-blue"></i>Import
            </a>         
        </div>
    </div>
</div>
<div class="body-reset container">
    <div class="quickstart qs_main">
        <h1>Import a website.</h1>
        <legend>Upload a compatible website archive.</legend>
        <p>

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