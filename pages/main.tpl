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
    .quickstart input[type="radio"] {
        transform: scale(1.3);
        margin: 5px;
    }
    .quickstart label {
        vertical-align: text-top;
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
        <h1>Devstia makes it easy to create websites.</h1>
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
    (function($){
        $(function() {

            // Fading top-bar
            var fadeTopBarTo = false;
            function fadeTopBar() {
                if (fadeTopBarTo != false) {
                    clearTimeout(fadeTopBarTo);
                }
                fadeTopBarTo = setTimeout(() => {
                    $('.top-bar').fadeOut();
                }, 5000);
            }
            addEventListener("scroll", (event) => {
                if (!$('.top-bar').is(':visible')) {
                    $('.top-bar').fadeIn();
                }
                fadeTopBar();
            });
            $('.top-bar').on('mousemove', () => {
                fadeTopBar();
            });
            document.addEventListener('mousemove', function(event) {
                let mouseY = event.clientY;
                if (mouseY <= 20) {
                    if (!$('.top-bar').is(':visible')) {
                        $('.top-bar').fadeIn();
                    }
                    fadeTopBar();
                }
            });
            $('.app-content .container:first').css('position','relative').css('top', '-43px');
            $('.toolbar').css('position', 'relative').css('top', '302px');
            $(".app-header").animate({
                'padding-top': '12px'
            }, 1000);
            $('.top-bar').fadeOut();
            $('.main-menu-item-link').click(function(e) {
                e.preventDefault();
                var mmURL = window.location.protocol + '//' + window.location.host + $(this).attr('href');
                $('.top-bar').fadeIn();
                $(".app-header").animate({
                    'padding-top': '40px'
                }, 1000, function() {
                    window.location = mmURL;
                });
            });

            // Match background gradient to theme
            function LightenDarkenColor(col, amt) {
                if (col.length == 4) {
                    col = col.split("").map((item)=>{
                    if(item == "#"){return item}
                        return item + item;
                    }).join("")
                }
                let usePound = false;
                if ( col[0] == "#" ) {
                    col = col.slice(1);
                    usePound = true;
                }
                let num = parseInt(col,16);
                let r = (num >> 16) + amt;
                if ( r > 255 ) r = 255;
                else if  (r < 0) r = 0;
                let b = ((num >> 8) & 0x00FF) + amt;
                if ( b > 255 ) b = 255;
                else if  (b < 0) b = 0;
                let g = (num & 0x0000FF) + amt
                if ( g > 255 ) g = 255;
                else if  ( g < 0 ) g = 0;
                return (usePound?"#":"") + (g | (b << 8) | (r << 16)).toString(16);
            }
            let bgColor = window.getComputedStyle(document.body).getPropertyValue('--chart-grid-color');
            let radial =  "radial-gradient(circle," + LightenDarkenColor(bgColor, 18) + " 0%," + LightenDarkenColor(bgColor, -35) + " 100%)," + LightenDarkenColor(bgColor, -35);
            $('.body-reset').css('background', radial);
        });
    })(jQuery);
</script>
