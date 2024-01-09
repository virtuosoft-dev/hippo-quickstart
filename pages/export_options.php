<?php require( 'header.php' ); ?>
<?php
    $db_details = $_SESSION['db_details'];
    $domain = $_GET['domain'];
    $dbs = $_GET['dbs'];
?>
<div class="toolbar">
    <div class="toolbar-inner">
        <div class="toolbar-buttons">
            <a href="?quickstart=export_details&domain=<?php echo $domain; ?>&dbs=<?php echo $dbs; ?>" class="button button-secondary button-back js-button-back" id="back">
                <i class="fas fa-arrow-left icon-blue"></i>Back			
            </a>
        </div>
        <div class="toolbar-buttons">
            <a href="" class="button" id="continue-button">
                <i class="fas fa-arrow-right icon-blue"></i>Continue
            </a>         
        </div>
    </div>
</div>
<div class="body-reset container">
    <div class="quickstart qs_main">
        <h1>Export options.</h1>
        <legend>Leave all items checked for default export options.</legend>
        <p>
            <input class="export_option" type="checkbox" id="cgi_bin" checked="checked"/>
            <label for="cgi_bin">Include ./cgi-bin folder.</label>
        </p>
        <p>
            <input class="export_option" type="checkbox" id="document_errors" checked="checked"/>
            <label for="document_errors">Include ./document_errors folder.</label>
        </p>
        <p>
            <input class="export_option" type="checkbox" id="nodeapp" checked="checked"/>
            <label for="nodeapp">Include ./nodeapp folder.</label>
        </p>
        <p>
            <input class="export_option" type="checkbox" id="private" checked="checked"/>
            <label for="private">Include ./private folder.</label>
        </p>
        <p>
            <input class="export_option" type="checkbox" id="public_html" checked="checked"/>
            <label for="public_html">Include ./public_html folder.</label>
        </p>
        <p>
            <input class="export_option" type="checkbox" id="exvc" checked="checked"/>
            <label for="exvc">Exclude version control files &amp; folders (.git*, .svn, .hg).</label>
        </p>
    </div>
</div>
<script>
    (function($){
        $(function() {
            // Checkbox click, update continue button options
            $('.export_option').on('click', function() {
                updateOptions();
            });

            function updateOptions() {
                let options = [];
                $('.export_option').each(function() {
                    if ( $(this).is(':checked') ) {
                        options.push($(this).attr('id'));
                    }
                });
                options = options.join(',');
                $('#continue-button').attr('href', '?quickstart=export_now&domain=<?php echo $domain; ?>&dbs=<?php echo $dbs; ?>&options=' + options );
            }
            updateOptions();
        });
    })(jQuery);
</script>