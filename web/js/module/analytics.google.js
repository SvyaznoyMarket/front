define(
    [
        'require', 'jquery', 'underscore'
    ],
    function (
        require, $, _
    ) {
        var $body = $('body')

            handlers = {
                'default': function($el, gaData, e) {
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
            var $el = $(e.target),
                dataGa = $el.data('ga')
            ;

            console.info('ga', dataGa, $el.data());

            if (ga && !_.isUndefined(ga) && _.isObject(dataGa)) {

                _.each(dataGa, function(data, handlerName) {
                    if (!handlers[handlerName]) {
                        handlerName = 'default';
                    }

                    handlers[handlerName]($el, data, e);
                });
            }
        })
    }
);