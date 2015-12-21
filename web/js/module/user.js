define(
    [
        'jquery', 'underscore'
    ],
    function (
        $, _
    ) {

        var $body = $('body');

        /*
        $body.on('click', '.js-user-menu', function(e) {
            var
                $el = $(this),
                url = $el.attr('href'),
                $container = $('.id-content-container')
            ;

            try {
                if (!url) {
                    throw {message: 'Не задан url'}
                }

                $.get(url).done(function(response) {
                    if (response.content) {
                        $container.html(response.content);
                    }
                });

                e.preventDefault();
            } catch (error) { console.error(error); }
        });
        */

        $body.on('click', '.js-user-subscribe-input', function() {
            var
                $el = $(this),
                data = $el.data('value'),
                isChecked = !!$el.is(':checked'),
                url = isChecked ? $el.data('setUrl') : $el.data('deleteUrl')
            ;

            try {
                if (!url) {
                    throw {message: 'Нет url'};
                }

                $.post(url, data).done(function(response) {
                    if (!response.success) {
                    }
                });
            } catch(error) { console.error(error) };
        });
    }
);