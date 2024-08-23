<?php require( 'header.php' ); ?>
<?php
    // Create a new job id
    $job_id = $hcpp->quickstart->create_job();
?>
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
<div class="toolbar" style="z-index:100;position:relative;">
    <div class="toolbar-inner">
        <div class="toolbar-buttons">
            <a href="?quickstart=main" class="button button-secondary button-back js-button-back" id="back-button">
                <i tabindex="300" class="fas fa-arrow-left icon-blue"></i>Back			
            </a>
        </div>
    </div>
</div>
<div class="body-reset container">
    <div class="quickstart qs_create">
        <h1>Create a New Website</h1>
        <div id="bpwait">Please wait. Loading latest blueprints.</div>
        <iframe id="bpcreate" style="display:none;"></iframe>
    </div>
</div>
<script>
    (function($) {
        $(function() {

            // Retrieve the list of blueprints from Devstia.com, passing the current application password
            $('.spinner-overlay').addClass('active');
            let darkMode = $('link[href*="dark.min.css"]').length > 0;
            $.ajax({
                url: 'https://devstia.com/wp-json/devstia-com/v1/protected-content?page_url=' + encodeURIComponent('https://devstia.com/blueprints/?quickstart=1&dark_mode=' + darkMode),
                type: 'GET',
                beforeSend: function ( xhr ) {
                    <?php

                        global $hcpp;
                        $user = $_SESSION['user'];
                        $app_cred = trim( $hcpp->run( "invoke-plugin quickstart_connect_now $user" ) );
                        echo "xhr.setRequestHeader( 'Authorization', 'Basic ' + btoa('" . $app_cred . "') );";
                    ?>
                },
                success: function(data) {
                    $('#bpwait').html("Click the blueprint icon you would like to use to create a new website.");

                   // Inject list of already downloaded blueprints and current job_id into a local JS variable
                    <?php
                        $user = $_SESSION['user'];
                        if ( is_dir( "/home/$user/web/blueprints") ) {
                            $blueprints = scandir( "/home/$user/web/blueprints" );
                            $blueprints = array_diff( $blueprints, array( '.', '..', '.DS_Store', '._.DS_Store' ) );
                            $blueprints = array_values( $blueprints );
                            $blueprints = json_encode( $blueprints );
                        }else {
                            $blueprints = '[]';
                        }
                    ?>
                    data = data.replace('</' + 'head>', '<' + 'script>var blueprints=<?php echo $blueprints; ?>;var jobID="<?php echo $job_id; ?>";var httpHost="<?php echo $_SERVER['HTTP_HOST']; ?>";</' + 'script></' + 'head>');
                    $('#bpcreate').attr('srcdoc', data).show();
                    $('.spinner-overlay').removeClass('active');
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log(textStatus, errorThrown);
                }
            });

            // Process create website for download/create
            window.addEventListener('message', function(event) {
                if (event.origin !== 'https://local.dev.pw:8083') return;
                if (event.data.type == 'download') {
                    if (event.data.url.indexOf('https://devstia.com/') !== 0) return;
                    $('.spinner-overlay').addClass('active');
                    setTimeout(function() {
                        window.location = '?quickstart=create_options&job_id=<?php echo $job_id; ?>&url=' + event.data.url;
                    }, 250);
                }
                if (event.data.type == 'ready') {
                    if (event.data.primaryHeight) {
                        $('#bpcreate').css('height', event.data.primaryHeight + 200 + 'px');
                    }
                }
            });
        });  
    })(jQuery);
</script>