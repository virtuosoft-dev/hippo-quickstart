<?php require( 'header.php' ); ?>
<?php
    // Create a new job
    $job_id = $hcpp->quickstart->create_job();
?>
<div class="toolbar">
    <div class="toolbar-inner">
        <div class="toolbar-buttons">
            <a href="?quickstart=main" class="button button-secondary button-back js-button-back" id="back-button">
                <i class="fas fa-arrow-left icon-blue"></i>Back			
            </a>
        </div>
        <div class="toolbar-buttons">
            <a href="?quickstart=create_new" class="button" id="continue-button">
                <i class="fas fa-arrow-right icon-blue"></i>Continue
            </a>         
        </div>
    </div>
</div>
<div class="body-reset container">
    <div class="quickstart qs_create">
        <h1>Create a New Website</h1>
        <div id="bpwait">Please wait. Loading latest blueprints.</div>
        <iframe id="bpcreate" style="display:none;" src="https://local.dev.pw:8083/pluginable.php?load=quickstart&action=proxy&url=https://devstia.com/blueprints/">
        </iframe>
    </div>
</div>
<style>
    #bpcreate {
        border: none;
        min-height: 1280px;
        width: 900px;
    }
    @media (min-width: 680px) and (max-width: 1023px) {
        #bpcreate {
            width: 680px;
        }
    }
    @media (max-width: 679px) {
        #bpcreate {
            width: 480px;
        }
    }
</style>
<script>
    (function($) {
        $(function() {
            // Receive messages from iframe with wrapper height
            $('.spinner-overlay').addClass('active');
            window.addEventListener('message', function(event) {
                if (event.origin !== 'https://local.dev.pw:8083') return;
                if (!event.data.type) return;

                // Process display ready, adjust height
                if (event.data.type == 'ready') {
                    $('#bpcreate').css('display', 'block');
                    $('#bpwait').css('display', 'none');
                    $('.spinner-overlay').removeClass('active');
                    if (event.data.height) {
                        $('#bpcreate').css('height', event.data.height + 'px');
                    }
                }

                // Process iFrame download request on our local server
                if (event.data.type == 'download') {
                    window.location = '?quickstart=create_options&job_id=<?php echo $job_id; ?>&url=' + event.data.url;
                }
            });
        });  
    })(jQuery);
</script>


