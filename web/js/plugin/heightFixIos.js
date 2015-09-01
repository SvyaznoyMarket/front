define(
    [
        'jquery', 'underscore'
    ],
    function (
        $, _
    ) {
        var

            iOSFix = (function(){

                return {
                    init : function() {

                        iOS.init();

                        fixViewportHeight.init();

                        footerFix.init();

                    }
                }
            })(),

            /**
             * фиксация футера, если на странице слишком мало контента
             */

            footerFix = (function(){
                var
                    footer = $('.footer'),
                    body = $('body'),
                    hasNoIosClass = body.hasClass('noIOS');

                return {
                    init: function(){

                        footer.removeAttr('style');
                        hasNoIosClass && body.addClass('noIOS');

                        if ( ($('.content').height() + footer.outerHeight() + $('.header').height() < ($(window).height()) && (body.data('module')) !== 'index') ){
                            body.removeClass('noIOS');
                            footer.css('position','fixed');
                        }
                    }
                }
            })(),

            /**
             * проверка платформы (в iOS делаем css фикс, который не нужен на android)
             */
            iOS = (function() {

                var iDevices = [
                    'iPad Simulator',
                    'iPhone Simulator',
                    'iPod Simulator',
                    'iPad',
                    'iPhone',
                    'iPod'
                    ],
                    checkIOS = function(){
                        console.info(navigator.platform);

                        while (iDevices.length) {
                            if (navigator.platform === iDevices.pop()){ return true; }
                        }

                        return false;
                    };
                return {
                    init: function(){

                        //check if iOS
                        if ( !checkIOS() ){
                            $('body').addClass('noIOS') ;
                        }
                    }
                }
            })(),

            /**
             * фиксация бага iOS7(неверный расчет высоты окна) + события на ресайз
             * (полезно в случаях, когда есть страницы, которые должны  быть вписаны в окно браузера по высоте - например ЛК)
             */
            fixViewportHeight = (function() {

                var fixBodyHeight = function(){
                        var wh = $(window).height(),
                            ww = $(window).width(),

                            body = $('body');

                        document.documentElement.style.height = $(window).innerHeight + "px";
                        body.removeAttr('style');

                        if (document.body.scrollTop !== 0) {
                            $(window).scrollTo(0, 0);
                        }
                        console.log(body.height(),$(window).height());

                        if (wh < ww){
                            //landscape position
                            body.height(wh - 20);
                        }
                        console.log(body.height(),$(window).height());
                    };

                return {
                    init: function() {

                        //check if iOS7 & fix body height
                        if (
                            navigator.userAgent.match(/iPad;.*CPU.*OS 7_\d/i) &&
                            window.innerHeight != document.documentElement.clientHeight
                        ) {
                            window.addEventListener("scroll", fixBodyHeight, false);
                            window.addEventListener("orientationchange", function(){
                                fixBodyHeight();
                                footerFix.init();
                            }, false);

                            fixBodyHeight();

                            document.body.style.webkitTransform = "translate3d(0,0,0)";
                        } else {
                            $(window).on("load resize", footerFix.init);
                        }

                    }
                }
            })()
            ;

        iOSFix.init();

    });
