<style>
    .qs_main legend {
        margin-bottom: 20px;
    }
    main .container {
        min-height: 300px;;
    }
    .quickstart {
        font-size: larger;
    }
</style>
<div class="toolbar">
    <div class="toolbar-inner">
        <div class="toolbar-buttons">
            <a class="button button-secondary button-back js-button-back" href="/list/web/" style="display: none;">
                <i class="fas fa-arrow-left icon-blue"></i>Back			
            </a>
        </div>
        <div class="toolbar-buttons">
            <a href="#" class="button">
                <i class="fas fa-arrow-right icon-blue"></i>Continue
            </a>         
        </div>
    </div>
</div>
<div class="body-reset container">
    <div class="quickstart qs_main">
        <h1>CodeGarden makes it easy to create websites.</h1>
        <legend>Choose an option &amp; click the "Continue" button:</legend>
        <p>
        <input name="qsOption" type="radio" id="qs_create" checked="checked"/>
        <label for="qs_create">Create a new website.</label>
        </p>
        <p>
        <input name="qsOption" type="radio" id="qs_edit" />
        <label for="qs_edit">Remove or copy a website.</label>
        </p>
        <p>
        <input name="qsOption" type="radio" id="qs_io" />
        <label for="qs_io">Import or export a website.</label>
        </p>
    </div>
</div>
<script>
    // Match background gradient to theme
    (function($){
        $(function() {
            var qcolor = window.getComputedStyle(document.body);
            var color_background = qcolor.getPropertyValue('--color-background');
            var top_bar_background = qcolor.getPropertyValue('--top-bar-background');
            var top_bar_border_bottom = qcolor.getPropertyValue('--top-bar-border-bottom');
            $('.body-reset').css('background', "radial-gradient(circle,#" + top_bar_background + " 0%,#" + color_background + " 100%),#" + top_bar_border_bottom);
        });
    })(jQuery);
</script>
