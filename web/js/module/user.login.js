define(
    [
        'jquery', 'underscore', 'jquery.maskedinput'
    ],
    function (
        $, _
    ) {
        var
            $authContainer = $('.js-authContainer')
        ;

        // изменение состояния блока авторизации
        $authContainer.on('changeState', function(e, state) {
            var
                $el = $(this)
            ;

            console.info({'$el': $el, 'state': state});

            if (state) {
                var
                    oldClass = $el.attr('data-state') ? ('state_' + $el.attr('data-state')) : null,
                    newClass = 'state_' + state
                    ;

                oldClass && $el.removeClass(oldClass);
                $el.addClass(newClass);
                $el.attr('data-state', state);
            }

            $('.js-resetForm, .js-authForm, .js-registerForm').trigger('clearError');
        });

        // клик по ссылкам
        $authContainer.find('.js-link').on('click', function(e) {
            var
                $el = $(e.target),
                state = $el.data('state')
            ;

            console.info({'state': state});
            $authContainer.trigger('changeState', [state]);
        });
        console.info($authContainer);

        $(".js-phone-mask").mask("+7 (999) 999 - 99 - 99");

        //hack for inputs cursor on iOS
        $('.wrapper').scroll(function () {
            var selected = $(this).find("input:focus");
            selected.blur();
        });
    }
);