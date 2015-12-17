define(
    [
        'jquery', 'underscore'
    ],
    function (
        $, _
    ) {

        var $body = $('body');

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
        })
    }
);