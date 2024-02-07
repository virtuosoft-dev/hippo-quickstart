<?php ob_start(); ?>
<style>
    .quickstart {
        min-width: 50%;
        min-height: 150px;
        font-size: larger;
        padding: 30px;
        margin: 30px;
    }
    .quickstart legend {
        margin-bottom: 20px;
    }
    main .body-reset {
        min-height: 300px;
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
    .qs_import_options label,
    .qs_export_options label {
        font-size: .85rem;
    }
    .qs_export_options h3,
    .qs_copy_details h3,
    .adv-trash {
        cursor: pointer;
    }
    #advanced-opt {
        max-width: 640px;
    }
    .adv-trash {
        text-align: center;
    }
    @media (min-width:1024px) {
        thead#adv-options-table tr {
            display: contents;
        }
        .qs_export_options .units-table-cell {
            line-height: 1.5em;
        }
    }
    @media (max-width:1024px) {
        thead#adv-options-table {
            display: contents;
            width: 100%;
        }
    }
    .qs_remove_copy .form-check {
        float: left;
    }
    .qs_remove_copy .alert {
        transform: scale(.9);
    }
    .qs_remove_details .alias-details {
        padding: 10px;
        font-style: italic;
    }
    .qs_remove_details #confirm-remove-opt input {
        margin-top: 6px;
    }
    .qs_remove_details #confirm-remove-opt label {
        font-weight: 400;
    }
    .qs_remove_details .action-warn {
        margin: 15px 0 -15px;
    }
</style>
<script>
    (function($){
        $(function() {
            
            // Support keyboard navigation of h3 dropdowns
            $('h3').each(function() {
                if ( $(this).attr('tabindex') != undefined ) {
                    $(this).on('keydown', function(e) {
                        if (e.keyCode == 13 || e.keyCode == 32) {
                            $(this).click();
                        }
                    });
                }
            });

            // Support keyboard navigation of quickstart buttons
            $('.toolbar a.button').each(function() {
                QSButtons(this);
            });
            $('.quickstart a.button').each(function() {
                QSButtons(this);
            });
            function QSButtons(self) {
                let i = $(self).children().first();
                if ( i.is('i') ) {
                    i.on('keydown', function(e) {
                        if (e.keyCode == 13 || e.keyCode == 32) {
                            $(self)[0].click();
                        }
                    });
                }
            }

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
    global $hcpp;
    echo $hcpp->do_action('quickstart_header', $page);
?>