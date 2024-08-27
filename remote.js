(function($) {
    $(function() {

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

        // Update all anchor href attributes that aren't target="_blank" and not zips
        $('a[href^="https://devstia.com/"]:not([target="_blank"]):not([href$=".zip"])').each(function() {
            let $this = $(this);
            let href = $this.attr('href');
            $this.attr('href', 'javascript:window.parent.navigateToURL("' + href + '")');
        });

        // Show the display after content is loaded
        $(window.parent.bpcreate).show();
    });  
})(jQuery);

