define(
    [
        'jquery', 'underscore'
    ],
    function (
        $, _
    ) {
        var
            footerResize = function footerResize() {
                var
                    footer = $('.footer').removeAttr('style'),
                    body = $('body');

                console.log('body: ', body.length);

                if ( $('.content').height() + footer.outerHeight() + $('.header').height() < ($(window).height()) ){
                    console.log('remove remove')

                    $('body').removeClass('noIOS');
                    footer.css('position','fixed');
                }

            },

            iOS = function iOS() {

                var iDevices = [
                    'iPad Simulator',
                    'iPhone Simulator',
                    'iPod Simulator',
                    'iPad',
                    'iPhone',
                    'iPod'
                ];
                console.info(navigator.platform);
                while (iDevices.length) {
                    if (navigator.platform === iDevices.pop()){ return true; }
                }

                return false;
            }
            ;

        if (
            navigator.userAgent.match(/iPad;.*CPU.*OS 7_\d/i) &&
            window.innerHeight != document.documentElement.clientHeight
        ) {
            var fixViewportHeight = function() {
                var wh = $(window).height(),
                    ww = $(window).width(),
                    body = $('body');

                document.documentElement.style.height = $(window).innerHeight + "px";
                body.removeAttr('style');

                if (document.body.scrollTop !== 0) {
                    $(window).scrollTo(0, 0);
                }
                if (wh < ww){
                    //landscape position

                    body.height(wh - 20);
                }
            };

            //alert('ios7!!!');
            window.addEventListener("scroll", fixViewportHeight, false);
            window.addEventListener("orientationchange", function(){
                fixViewportHeight();
                footerResize();
            }, false);

            fixViewportHeight();

            document.body.style.webkitTransform = "translate3d(0,0,0)";
        } else {
            $(window).on("load resize", footerResize);
        }

        //check if iOS
        if ( !iOS() ){
            $('body').addClass('noIOS') ;
        }

        footerResize();
    }
);