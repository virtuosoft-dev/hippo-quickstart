(function($) {
    $(function() {
        // Make headings collapsible
        // $('h3.tb-heading').html('<span class="triangle-down"></span><a href="#">' + $('h3.tb-heading').text() + '</a>').click(function() {
        //     $(this).next().toggle();
        //     $(this).find('span').toggleClass('triangle-down triangle-up');
        //     return false;
        // });

        // Post a message to the parent window that we're ready with wrapper height
        window.parent.postMessage( {
            "type": "ready",
            "height": $('#wrapper').height()
        }, 'local.dev.pw:8083' );
    });  
})(jQuery);
