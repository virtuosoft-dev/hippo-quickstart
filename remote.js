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
        }, 'https://local.dev.pw:8083' );

        // Intercept click for zip download
        $('.bpcard a[href*=".zip"]').each(function(){
            var $this = $(this);
            $this.click(function(e){
                e.preventDefault();
                var href = $this.attr('href');
                
                // Post a message to the parent window of where to find the zip file
                window.parent.postMessage( {
                    "type": "download",
                    "url": href
                }, 'https://local.dev.pw:8083' );
            });
        });
    });  
})(jQuery);
