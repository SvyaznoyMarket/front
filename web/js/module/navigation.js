define(
    ['jquery', 'jquery.scrollTo'],
    function ($) {

        var $snapContent = $('#wrapper');
        // end of vars


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

            }
            ;
        // end of vars
        
        $(navIco).on('click', showHideMenu);
        $(fader).on('click', showHideMenu);

        $(document)
            .on('focus', 'input, textarea, input + label, select', function(e) {
                body.addClass('fixfixed');
            })
            .on('blur', 'input, textarea, input + label, select', function(e) {
                body.removeClass('fixfixed');
            });

    }
);