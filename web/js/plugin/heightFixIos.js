define(
    [
        'jquery', 'underscore'
    ],
    function (
        $, _
    ) {

        if (
            navigator.userAgent.match(/iPad;.*CPU.*OS 7_\d/i) &&
            window.innerHeight != document.documentElement.clientHeight
        ) {
            var fixViewportHeight = function() {
                var wh = $(window).height(),
                    ww = $(window).width();
                document.documentElement.style.height = window.innerHeight + "px";
                $('body').removeAttr('style');

                if (document.body.scrollTop !== 0) {
                    window.scrollTo(0, 0);
                }
                if (wh < ww){
                    //landscape position

                    $('body').height(wh - 20);
                }
            };

            //alert('ios7!!!');
            window.addEventListener("scroll", fixViewportHeight, false);
            window.addEventListener("orientationchange", fixViewportHeight, false);
            fixViewportHeight();

            document.body.style.webkitTransform = "translate3d(0,0,0)";
        }
    }
);