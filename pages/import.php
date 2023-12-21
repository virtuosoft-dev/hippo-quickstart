<?php require( 'header.php' ); ?>
<div class="toolbar">
    <div class="toolbar-inner">
        <div class="toolbar-buttons">
            <a href="?quickstart=import_export" class="button button-secondary button-back js-button-back" id="back">
                <i class="fas fa-arrow-left icon-blue"></i>Back			
            </a>
        </div>
        <div class="toolbar-buttons">
            <a href="#" class="button disabled" id="import-button">
                <i class="fas fa-arrow-right icon-blue"></i>Import
            </a>         
        </div>
    </div>
</div>
<div class="body-reset container">
    <div class="quickstart qs_main">
        <h1>Import a website.</h1>
        <legend>Upload a <a href="https://devstia.com/compatible-website-archive" target="_blank">compatible website archive</a>.</legend>
            <input type="file" id="fileInput" name="fileInput" style="display: none;" />
            <div id="dropZone">
                <i class="fas fa-upload"></i><br>
                Drop file here or click to upload.<br>
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
                var formData = new FormData();
                formData.append('file', file);
                $.ajax({
                    url: '../../pluginable.php?load=quickstart&action=upload',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(data) {
                        console.log('File uploaded successfully.');
                    },
                    error: function() {
                        console.log('Failed to upload file.');
                    }
                });
            }

            // Update the continue button href based on selected qsOption
            $('input[name="qsOption"]').on('change', (e) => {
                let qsOption = $('input[name="qsOption"]:checked').attr('id');
                $('#continue-button').attr('href', '?quickstart=' + qsOption);
            });
        });
    })(jQuery);
</script>