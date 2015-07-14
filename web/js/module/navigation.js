define(
    ['jquery', 'jquery.scrollTo', 'module/snap'],
    function ($) {

        var chooseModelWrap = $('.chooseModel'),
            chooseModelMoreLink = chooseModelWrap.find('.chooseModel_moreLink'),
            chooseModelMoreBox = chooseModelWrap.find('.chooseModel_moreBox'),
            chooseModelMoreBoxDown = chooseModelWrap.find('.chooseModel_moreBox.more'),

            chooseModelMoreModel = function chooseModelMoreModel() {
                chooseModelMoreBox.slideToggle('800');
                chooseModelMoreLink.toggleClass('more');
            },
            snapper = new Snap({
                element: document.getElementById('wrapper'),
                disable: 'right'
            })
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

                body.hasClass('snapjs-left') ? snapper.close() : snapper.open('left');
            },

            footerResize = function footerResize() {
                var
                    footer = $('.footer');

                footer.css('position', body.height() + footer.innerHeight() > $(window).height() ? 'inherit' : 'fixed');
            };
        // end of vars

        $(window).on('load resize', footerResize);
        $(navIco).on('click', showHideMenu);

        $(document)
            .on('focus', 'input, textarea, input + label, select', function(e) {
                body.addClass('fixfixed');
            })
            .on('blur', 'input, textarea, input + label, select', function(e) {
                body.removeClass('fixfixed');
            });
    }
);