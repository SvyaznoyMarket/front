define(
    [
        'jquery',
    ],
    function (
        $
    ) {
        var $body = $('body');

        $('.js-authTab').on('click', function(e) {
            var $el = $(e.target);

            $('.js-authTab').each(function(i, el) {
                var $el = $(el)
                    $content = $($el.data('contentSelector'))
                ;

                $content.slideUp('fast');
                $el.addClass('borderBd');
            });

            $($el.data('contentSelector')).slideDown('fast');
            $el.removeClass('borderBd');
        });

    }
);