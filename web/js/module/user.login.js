define(
    [
        'jquery', 'underscore'
    ],
    function (
        $, _
    ) {
        var
            $body = $('body'),
            urlHash = $(location).attr('hash') ? $(location).attr('hash').substring(1) : null;
        ;

        $('.js-authTab').on('click', function(e) {
            var
                $el = $(e.target),
                $content = $($el.data('contentSelector')),
                urlHash = $el.data('urlHash')
            ;

            if ($content.is(':visible')) {
                return false;
            }

            $('.js-authTab').each(function(i, el) {
                var $el = $(el),
                    $content = $($el.data('contentSelector'))
                ;

                $content.slideUp('fast');
                $el.addClass('borderBd');
            });

            $content.slideDown('fast');
            $el.removeClass('borderBd');

            if (urlHash) {
                $(location).attr('hash', urlHash);
            } else {
                $(location).removeAttr('hash');
            }
        });

        $('.js-authLoginLink').on('click', function(e) {
            var $content = $($(e.target).data('contentSelector'));

            if ($content.length) {
                $('.js-authLoginContent').each(function(i, el) {
                    var $el = $(el),
                        $input = $el.find(':text')
                    ;
                    $el.hide();
                    $input.data('value', $input.val());
                    $input.val('');
                    console.warn($input);
                });
            }

            $content.show();
            var $input = $content.find(':text');
            $input.val($input.data('value'));
        });


        if (urlHash) {
            $('.js-authTab').filter('[data-url-hash="' + urlHash + '"]').trigger('click', [false]);
        }
    }
);