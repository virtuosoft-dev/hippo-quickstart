<?php require( 'header.php' ); ?>
<?php
    $job_id = $hcpp->quickstart->create_job();
    $cpdomain = trim( shell_exec( 'hostname -f' ) );
?>
<style>
    #bpcreate {
        border: none;
        min-height: 1280px;
        width: 1000px;
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
    .qs_create {
        padding: 0;
        margin: 0;
    }
</style>
<div class="toolbar" style="z-index:100;position:relative;">
    <div class="toolbar-inner">
        <div class="toolbar-buttons">
            <a href="#" class="button button-secondary button-back js-button-back" id="back-button">
                <i tabindex="300" class="fas fa-arrow-left icon-blue"></i>Back			
            </a>
        </div>
        <div class="toolbar-buttons">
            <a href="#" class="button" id="create-button" style="display: none;">
                <i tabindex="200" class="fas fa-arrow-right icon-blue"></i>Create
            </a>         
        </div>
    </div>
</div>
<div class="body-reset container">
    <div class="quickstart qs_create">
        
        <h1 style="padding: 50px 50px 0;">Create a New Website</h1>
        <div id="bpwait" style="padding: 0 50px;">Please wait. Loading latest blueprints.</div>
        <iframe id="bpcreate" src="https://<?php echo $cpdomain; ?>:8083/pluginable.php?q=https://devstia.com/wp-login.php?redirect_to=https://devstia.com/blueprints" style="display:none;"></iframe>
    </div>
</div>
<script>
    (function($) {
        $(function() {

            // Set the job ID and HTTP host for app connect
            window.jobID = '<?php echo $job_id; ?>';
            window.httpHost = '<?php echo $_SERVER['HTTP_HOST']; ?>';
            window.bpHistory = [];

            // Listen for messages from the iframe
            window.addEventListener('message', function(event) {
                if (event.origin !== 'https://<?php echo $cpdomain; ?>:8083') return;

                // Signal the iframe is ready to be displayed
                if (event.data.action == 'loaded') {
                    $('div.qs_create h1').hide();
                    $('#bpwait').hide();
                    $('#bpcreate').show();
                    $('.spinner-overlay').removeClass('active');
                }

                // Display the spinner overlay on unloaded
                if (event.data.action == 'unloading') {
                    $('#bpcreate').hide();
                    $('.spinner-overlay').addClass('active');
                    $('#create-button').hide();
                }

                // Supply connected credentials to the iframe
                if (event.data.action == 'requestAppCredentials') {
                    let data = {
                        action: 'replyAppCredentials',
                        app_cred: '<?php 
                            global $hcpp;
                            $user = $_SESSION['user'];
                            $app_cred = trim( $hcpp->run( "invoke-plugin quickstart_connect_now $user" ) );
                            echo $app_cred; 
                        ?>'
                    };

                    // Post back the application credentials
                    event.source.postMessage(data, event.origin);
                }

                // Record iframe url history on loaded event
                if (event.data.action == 'loaded') {
                    window.bpHistory.push($('#bpcreate')[0].contentWindow.location.href);
                }

                // Setup the create button
                if (event.data.action == 'bpcreate') {
                    $('#create-button').attr('url', event.data.zip);
                    $('#create-button').show();
                }
                if (event.data.action == 'createnow') {
                    $('#create-button').click();
                }
            });
            $('.spinner-overlay').addClass('active');

            // Handle create button
            $('#create-button').on('click', function(e) {
                e.preventDefault();
                let url = $(this).attr('url');
                if (url.indexOf('https://devstia.com/') !== 0 &&
                    url.indexOf('https://github.com/') !== 0 && 
                    url.indexOf('https://codeload.github.com/') !==0 ) return;
                $('.spinner-overlay').addClass('active');
                setTimeout(function() {
                    window.location = '?quickstart=create_options&job_id=<?php echo $job_id; ?>&url=' + url;
                }, 250);
            });

            // Handle back button on iframe
            $('#back-button').on('click', function(e) {
                e.preventDefault();
                window.bpHistory.pop();
                let prevURL = window.bpHistory.pop();
                if (prevURL) {
                    $('#bpcreate')[0].contentWindow.location = prevURL;
                }else{
                    window.location = '?quickstart=main';
                }
            });

            // Handle resizing the view port
            window.resizeBPCreate = function( height ) {
                if (height < 1280) height = 1280;
                $('#bpcreate').css('height', height + 'px');
            }
        });
    })(jQuery);
</script>