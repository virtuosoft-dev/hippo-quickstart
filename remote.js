(function($) {
    $(function() {

        // Make bcard tabable
        $('.bpcard img').attr('tabindex', '100');

        // Post a message to the parent window that we're ready 
        setTimeout(function() {
            window.parent.postMessage({
                "type": "ready",
                "primaryHeight": $('#primary').height()
            }, 'https://local.dev.pw:8083');
        }, 1000);

        // Change the URL to include the jobID to validate storing application credentials
        $('.connect-devstia a').each(function() {
            let $this = $(this);
            let href = $this.attr('href');
            $this.attr('href', href + '?jobID=' + jobID);
        });

        // Intercept click for zip download
        $('.bpcard a[href*=".zip"]').each(function() {
            let $this = $(this);
            $this.click(function(e) {
                e.preventDefault();
                let href = $this.attr('href');
                
                // Post a message to the parent window of where to find the zip file
                window.parent.postMessage( {
                    "type": "download",
                    "url": href
                }, 'https://local.dev.pw:8083' );
            });
        });

        // Remove download icon on already downloaded blueprints
        $('.bpcard .wp-block-image a').each(function() {
            let bpf = $(this).attr('href');
            bpf = bpf.split('/').pop().replace('.zip', '');
            if (blueprints.includes(bpf)) {
                let bpcard = $(this).parent().parent().parent();
                bpcard.find('.bpbutton').css('visibility', 'hidden');
            }
        });

        // Update all anchor href attributes that start with https://devstia.com/ to use window.navigateURL
        $('a[href^="https://devstia.com/"]').each(function() {
            let $this = $(this);
            let href = $this.attr('href');
            $this.attr('href', 'javascript:window.parent.navigateToURL("' + href + '")');
        });

        // Show the display after content is loaded
        $(window.parent.bpcreate).show();
    });  
})(jQuery);
