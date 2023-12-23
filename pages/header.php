<?php ob_start(); ?>
<style>
    .qs_main {
        min-width: 50%;
        min-height: 150px;
    }
    .qs_main legend {
        margin-bottom: 20px;
    }
    main .body-reset {
        min-height: 300px;
    }
    .quickstart {
        font-size: larger;
        padding: 30px;
        margin: 30px;
    }
    .quickstart input[type="radio"] {
        transform: scale(1.3);
        margin: 5px;
    }
    .quickstart label {
        vertical-align: text-top;
    }
    .quickstart .toolbar-right {
        width: 100%;
    }
    .quickstart .toolbar-search {
        margin-left: auto;
        padding: 8px 15px;
    }
    .quickstart .units-table-header .units-table-cell {
        padding: 5px 15px;
    }
    .disabled {
        opacity: 0.4;
        filter: saturate(0);
    }
    .ref-files {
        font-weight: normal;
        font-size: x-small;
    }
    #dropZone {
        text-align:center;
        border:3px dashed;
        padding:30px;
    }
    #dropZone.dragover {
        background-color: #eee;
    }
</style>
<script>
    (function($){
        $(function() {
            
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
<?php
    $page = ob_get_clean();
    echo $hcpp->do_action('quickstart_header', $page);
?>