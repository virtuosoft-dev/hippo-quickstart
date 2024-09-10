<?php require( 'header.php' ); ?>
<?php
    // Create a new job and record user
    $job_id = $hcpp->quickstart->create_job();
    $hcpp->quickstart->set_job_data( $job_id, 'user', $_SESSION['user'] );
    $hcpp->quickstart->xfer_job_data( $job_id, 'user' );

    // Start the upload server on demand
    $hcpp->quickstart->start_upload_server();
?>
<div class="toolbar">
    <div class="toolbar-inner">
        <div class="toolbar-buttons">
            <a href="#" class="button button-secondary button-back js-button-back" id="back-button">
                <i tabindex="300" class="fas fa-arrow-left icon-blue"></i>Back			
            </a>
        </div>
        <div class="toolbar-buttons">
            <a href="#" class="button disabled" id="continue-button">
                <i tabindex="200" class="fas fa-arrow-right icon-blue"></i>Continue
            </a>         
        </div>
    </div>
</div>
<div class="body-reset container">
    <div class="quickstart qs_import">
        <h1>Import a Website</h1>
        <legend>Upload a <a href="https://devstia.com/docs/compatible-website-archive" target="_blank">compatible website archive</a>.</legend>
            <input type="file" id="fileInput" name="fileInput" style="display: none;" />
            <div id="dropZone" tabindex="100">
                <i class="fas fa-upload"></i><br>
                Drop file here or click to upload.
            </div>
    </div>
</div>
<script>
    (function($){
        $(function() {
            var dropZone = $('#dropZone');
            var fileInput = $('#fileInput');

            // Handle click on drop zone to trigger file input click
            dropZone.on('click', function() {
                fileInput.click();
            });
            dropZone.on('keydown', function(e) {
                if (e.keyCode == 13 || e.keyCode == 32) {
                    $(this)[0].click();
                }
            });

            // Handle file input change
            fileInput.on('change', function() {
                var file = this.files[0];
                uploadFile(file);
            });

            // Handle drag and drop
            dropZone.on('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('dragover');
            });

            dropZone.on('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
            });

            dropZone.on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
                var file = e.originalEvent.dataTransfer.files[0];
                uploadFile(file);
            });

            // Cancel the export
            $('#back-button').on('click', (e) => {
                e.preventDefault();
                $.ajax({
                    url: '../../pluginable.php?load=quickstart&action=cancel_job&job_id=<?php echo $job_id; ?>',
                    type: 'GET',
                    success: function( data ) {
                        window.location = "?quickstart=import_export"
                    }
                });
            });

            // Upload file to server
            function uploadFile(file) {
    
                // Show the progress bar
                var progressBar = $('<progress>').attr({ value: 0, max: 100 });
                $('#dropZone').html('');
                $('#dropZone').append(progressBar);

                var formData = new FormData();
                formData.append('file', file);
                
                // Derive the fully qualified URL
                var fqurl = window.location.protocol + "//" + window.location.host + "/quickstart-upload/?job_id=<?php echo $job_id; ?>";
                $.ajax({
                    url: fqurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    xhr: function() {
                        var xhr = new window.XMLHttpRequest();
                        xhr.upload.addEventListener('progress', function(evt) {
                            if (evt.lengthComputable) {
                                var percentComplete = evt.loaded / evt.total * 100;
                                progressBar.val(percentComplete);
                            }
                        }, false);
                        return xhr;
                    },
                    success: function(data) {
                        try {
                            data = JSON.parse(data);
                        } catch (e) {
                            data = { status: 'error', message: 'Unknown error occurred: [' + data + ']' }; 
                        }
                        if (data.status == 'uploaded') {
                            $('#dropZone').html('<i class="fas fa-file-archive"></i><br>' + data.message + '</span>');
                            $('#continue-button').attr('href', '?quickstart=import_options&job_id=<?php echo $job_id; ?>');
                            $('#back-button').attr('href', '?quickstart=import_export&job_id=<?php echo $job_id; ?>');
                            $('#continue-button').removeClass('disabled');
                        }else{
                            $('#dropZone').html('<i class="fas fa-exclamation-triangle" style="color:orange;"></i><br>' + data.message + '</span>');
                        }
                    },
                    error: function() {
                        // Reset the progress bar
                        $('#dropZone').html('<i class="fas fa-exclamation-triangle" style="color:orange;"></i><br>Upload failed. Please try again.</span>');
                    }
                });
            }
        });
    })(jQuery);
</script>