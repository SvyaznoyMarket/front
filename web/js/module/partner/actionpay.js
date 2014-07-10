define(
    [
        'require', 'jquery'
    ],
    function (
        require, $
    ) {
        var $body = $('body'),

            handle = function(action, data, $el) {
                window.APRT_DATA = data;
                console.info('partner', 'actionpay', window.APRT_DATA);

                require(['//rt.actionpay.ru/code/enter/']);
            }
        ;

        return {
            handle: handle
        }
    }
);