define(
    ['jquery', 'jquery.scrollTo'],
    function ($) {

        var chooseModelWrap = $('.chooseModel'),
            chooseModelMoreLink = chooseModelWrap.find('.chooseModel_moreLink'),
            chooseModelMoreBox = chooseModelWrap.find('.chooseModel_moreBox'),
            chooseModelMoreBoxDown = chooseModelWrap.find('.chooseModel_moreBox.more'),

            chooseModelMoreModel = function chooseModelMoreModel() {
                chooseModelMoreBox.slideToggle('800');
                chooseModelMoreLink.toggleClass('more');
            },
            $snapContent = $('#wrapper');
            ;
        // end of vars

        chooseModelMoreLink.click(chooseModelMoreModel);

        /**
         * Навигация сайта, показывается при клике по иконке .navIco
         */
        var
            body = $('body'),
            fader = $('.js-fader'),
            navIco = $('.js-nav-open-link'),

            showHideMenu = function showHideMenu(e) {

                e.preventDefault();

                if (body.hasClass('snapjs-left')) {
                    $snapContent.removeClass('shifted');
                    body.removeClass('snapjs-left');
                } else {
                    $snapContent.addClass('shifted');
                    body.addClass('snapjs-left');
                }

            },

            footerResize = function footerResize() {
                var
                    footer = $('.footer');
                if ( $('.content').height() + footer.innerHeight() + $('.header').height()< $(window).height() ){
                    body.removeClass('noIOS');
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
        // end of vars

        $(window).on('load resize', footerResize);
        $(navIco).on('click', showHideMenu);
        $(fader).on('click', showHideMenu);

        $(document)
            .on('focus', 'input, textarea, input + label, select', function(e) {
                body.addClass('fixfixed');
            })
            .on('blur', 'input, textarea, input + label, select', function(e) {
                body.removeClass('fixfixed');
            });

        //check if iOS
        if ( !iOS() ){
            $(body).addClass('noIOS') ;
        }


    }
);