<?php require( 'header.php' ); ?>
<?php
    // Create a new job
    $job_id = $hcpp->quickstart->create_job();
?>
<div class="toolbar">
    <div class="toolbar-inner">
        <div class="toolbar-buttons">
            <a href="?quickstart=main" class="button button-secondary button-back js-button-back" id="back">
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
        <legend>Choose a blueprint to create your new website:</legend>
        <p>

        </p>
    </div>
</div>
