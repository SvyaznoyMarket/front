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
    }
);