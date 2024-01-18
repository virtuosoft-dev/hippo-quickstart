<?php require( 'header.php' ); ?>
<?php
    global $hcpp;
    $import_key = $hcpp->nodeapp->random_chars( 16 );
    $_SESSION['import_key'] = $import_key;
?>
<div class="toolbar">
    <div class="toolbar-inner">
        <div class="toolbar-buttons">
            <a href="?quickstart=import_export" class="button button-secondary button-back js-button-back" id="back">
                <i class="fas fa-arrow-left icon-blue"></i>Back			
            </a>
        </div>
        <div class="toolbar-buttons">
            <a href="#" class="button disabled" id="continue-button">
                <i class="fas fa-arrow-right icon-blue"></i>Continue
            </a>         
        </div>
    </div>
</div>
<div class="body-reset container">
    <div class="quickstart qs_import">
        <h1>Import a Website</h1>
        <legend>Upload a <a href="https://devstia.com/compatible-website-archive" target="_blank">compatible website archive</a>.</legend>
            <input type="file" id="fileInput" name="fileInput" style="display: none;" />
            <div id="dropZone">
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

            // Upload file to server
            function uploadFile(file) {
    
                // Show the progress bar
                var progressBar = $('<progress>').attr({ value: 0, max: 100 });
                $('#dropZone').html('');
                $('#dropZone').append(progressBar);

                var formData = new FormData();
                formData.append('file', file);
                $.ajax({
                    url: '../../pluginable.php?load=quickstart&action=upload&import_key=<?php echo $import_key; ?>',
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
                            $('#continue-button').attr('href', '?quickstart=import_options&import_key=<?php echo $import_key; ?>');
                            $('#back').attr('href', '?quickstart=import_export&import_key=<?php echo $import_key; ?>');
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