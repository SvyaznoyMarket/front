define(
    ['jquery', 'jquery.scrollTo'],
    function ($) {
        /**
         * Навигация сайта, показывается при клике по иконке .navIco
         */
        var
            body = $('body'),
            fader = $('.js-fader'),
            navIco = $('.js-nav-open-link'),

            showMenu = function showHideMenu(e) {

                e.preventDefault();

                if (body.hasClass('menu-open')) {
                    body.removeClass('menu-open');
                } else {
                    body.addClass('menu-open');
                }
            },

            hideMenu = function hideMenu() {
                body.removeClass('menu-open');
            };
        // end of vars
        
        $(navIco).on('click', showMenu);
        $(fader).on('click', hideMenu);

        $(document)
            .on('focus', 'input, textarea, input + label, select', function(e) {
                body.addClass('fixfixed');
            })
            .on('blur', 'input, textarea, input + label, select', function(e) {
                body.removeClass('fixfixed');
            });
    }
);