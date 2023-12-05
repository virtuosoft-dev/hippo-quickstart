<?php require( 'header.php' ); ?>
<div class="toolbar nobar"></div>
<div class="body-reset container">
    <div class="quickstart qs_main">
        <h1>Devstia makes it easy to create websites.</h1>
        <legend>Choose an option &amp; click the "Continue" button:</legend>
        <p>
        <input name="qsOption" type="radio" id="create" checked="checked"/>
        <label for="create">Create a new website.</label>
        </p>
        <p>
        <input name="qsOption" type="radio" id="remove_copy" />
        <label for="remove_copy">Remove or copy a website.</label>
        </p>
        <p>
        <input name="qsOption" type="radio" id="import_export" />
        <label for="import_export">Import or export a website.</label>
        </p>
    </div>
</div>
<div class="toolbar">
    <div class="toolbar-inner">
        <div class="toolbar-buttons">
            <a href="?quickstart=main" class="button button-secondary button-back js-button-back" id="back" style="display: none;">
                <i class="fas fa-arrow-left icon-blue"></i>Back			
            </a>
        </div>
        <div class="toolbar-buttons">
            <a href="#" class="button" id="continue-button">
                <i class="fas fa-arrow-right icon-blue"></i>Continue
            </a>         
        </div>
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
            // $('#continue-button').on('click', (e) => {
            //     e.preventDefault();
            //     let qsOption = $('input[name="qsOption"]:checked').attr('id');
            //     window.location.href = '?quickstart=' + qsOption;
            // });
        });
    })(jQuery);
</script>
