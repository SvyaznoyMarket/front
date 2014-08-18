define(
    ['jquery'],
    function ($) {
        var body = $('body'),

            wrap = $('.js-kit-wrap'),
            open = $('.js-kit-open'),
            closer = $('.js-kit-close'),
            hiddenCnt= $('.js-kit-hidden');
        // end of vars

        var 
            /*  
             * показываем блок большого изображения
            */
            kitShow = function kitShow() {
                $('html, body').animate({scrollTop:0}, 'fast');
                wrap.slideDown().show(0);
                hiddenCnt.css({'overflow' : 'hidden'}).delay(100).animate({'opacity' : 0, 'height' : 0});

                return false;
            },

            /*  
             * скрываем блок большого изображения
            */
            kitHide = function kitHide() {
                wrap.slideUp().hide(0);
                hiddenCnt.css({'opacity' : 1, 'height' : 'auto', 'overflow' : 'visible'});
                
                return false;
            };
        // end of functions

        open.on('click', kitShow);
        closer.on('click', kitHide);
    }
);