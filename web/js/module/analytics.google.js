define(
    [
        'require', 'jquery', 'underscore'
    ],
    function (
        require, $, _
    ) {
        var $body = $('body')

            handlers = {
                'default': function($el, dataGa, e) {
                    ga.apply(ga, dataGa);
                },

                'm_add_to_basket': function($el, dataGa, e) {
                    var dataValue = $el.data('value');

                    _.each(dataGa, function(v, k) {
                        if ('{product.sum}' == v) {
                            if (dataValue && dataValue.product) {
                                dataGa[k] = dataValue.product.price * dataValue.product.quantity;
                            }
                        }
                    });

                    ga.apply(ga, dataGa);
                }
            }
        ;

        $body.on('click', '.js-ga-click', function(e) {
            var $el = $(this),
                dataGa = $el.data('ga')
            ;

            console.info('js-ga-click', $el, dataGa);
            if (ga && !_.isUndefined(ga) && _.isObject(dataGa)) {

                _.each(dataGa, function(data, handlerName) {
                    console.info('ga', handlerName, data);

                    if (!handlers[handlerName]) {
                        handlerName = 'default';
                    }

                    handlers[handlerName]($el, data, e);
                });
            }
        })
    }
);