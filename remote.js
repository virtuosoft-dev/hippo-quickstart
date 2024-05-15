(function($) {
    $(function() {
        // Make headings collapsible
        // $('h4.tb-heading').html('<span class="triangle-down"></span><a href="#">' + $('h4.tb-heading').text() + '</a>').click(function() {
        //     $(this).next().toggle();
        //     $(this).find('span').toggleClass('triangle-down triangle-up');
        //     return false;
        // });

        // Post a message to the parent window that we're ready 
        window.parent.postMessage( {
            "type": "ready"
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

        // Receive list of already downloaded blueprints from parent window
        window.addEventListener('message', function(event) {
            if (event.origin !== 'https://local.dev.pw:8083') {
                return;
            }
            if (event.data.type === 'blueprints') {
                var blueprints = event.data.blueprints;
                $('.bpcard .wp-block-image a').each(function() {
                    let bpf = $(this).attr('href');
                    bpf = bpf.split('/').pop().replace('.zip', '');
                    if (blueprints.includes(bpf)) {
                        let bpcard = $(this).parent().parent().parent();
                        bpcard.find('.bpbutton').css('visibility', 'hidden');
                    }
                });
            }
        });
    });  
})(jQuery);
