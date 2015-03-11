define(
    [
        'require', 'jquery'
    ],
    function (
        require, $
    ) {
        console.info('partner');

        $('.js-partner').each(function(i, el) {
            var $el = $(el),
                id = $el.data('id'),
                action = $el.data('action'),
                dataValue = $el.data('value')
            ;

            console.info('partner', id, action, dataValue, $el);
            require(['module/partner/' + id], function(module) {
                try {
                    module.handle && module.handle(action, dataValue, $el);
                } catch (error) {
                    console.warn('partner', id, error);
                }
            });
        });
    }
);