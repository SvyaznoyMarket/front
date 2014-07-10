define(
    [
        'require', 'jquery'
    ],
    function (
        require, $
    ) {
        console.info('parner');

        $('.js-partner').each(function(i, el) {
            var $el = $(el)
                id = $el.data('id'),
                action = $el.data('action'),
                dataValue = $el.data('value')
            ;

            console.info('parner', $el, $el.data());
            require(['module/partner/' + id], function(module) {
                module.handle(action, dataValue, $el);
            });
        });
    }
);